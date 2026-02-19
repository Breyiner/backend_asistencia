<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\ResourceChanged;
use App\Models\Apprentice;
use App\Models\Attendance;
use App\Models\Ficha;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\RealClass;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Listener que notifica al gestor de fichas sobre
 * inasistencias y clases reales registradas.
 *
 * Escucha el evento ResourceChanged y actúa solo en casos específicos:
 * - Attendance actualizado (inasistencia registrada)
 * - RealClass creada (nueva clase real)
 *
 * NO implementa ShouldQueue, por lo que se ejecuta sincrónicamente.
 * Esto garantiza que la notificación se envía en el mismo request.
 *
 * Registro en EventServiceProvider:
 * ResourceChanged::class => [
 *     NotifyGestorOnAttendanceOrRealClass::class,
 * ]
 */
class NotifyGestorOnAttendanceOrRealClass
{
    /**
     * Maneja el evento ResourceChanged.
     *
     * Proceso:
     * 1. Verifica que el subject sea Attendance o RealClass
     * 2. Verifica que la acción sea la permitida para cada tipo
     * 3. Determina el tipo de notificación
     * 4. Obtiene la ficha relacionada al recurso
     * 5. Valida y obtiene el gestor de la ficha
     * 6. Crea y transmite la notificación
     *
     * @param ResourceChanged $event Evento con acción, tipo y ID del recurso
     */
    public function handle(ResourceChanged $event): void
    {
        // Solo procesa Attendance y RealClass, ignora cualquier otro modelo
        if (!in_array($event->subjectType, [Attendance::class, RealClass::class], true)) {
            return;
        }

        // Define qué combinaciones de tipo+acción son válidas:
        // - Attendance: solo cuando se actualiza (se registra inasistencia)
        // - RealClass: solo cuando se crea (nueva clase registrada)
        $allowed =
            ($event->subjectType === Attendance::class && $event->action === 'updated') ||
            ($event->subjectType === RealClass::class  && $event->action === 'created');

        // Si la acción no es de las permitidas, no hace nada
        if (!$allowed) {
            return;
        }

        // Determina la clave del tipo de notificación según el caso
        $typeKey = match (true) {
            $event->subjectType === Attendance::class && $event->action === 'updated' => 'resource_updated',
            $event->subjectType === RealClass::class  && $event->action === 'created' => 'resource_created',
        };

        // Busca el tipo de notificación en la BD
        $notificationType = NotificationType::where('key', $typeKey)->first();

        // Si no existe el tipo de notificación configurado, no hace nada
        if (!$notificationType) {
            return;
        }

        // Variables para determinar la ficha y nombre del aprendiz
        $fichaId        = null;
        $apprenticeName = null;

        /**
         * CASO 1: Attendance actualizado.
         *
         * Obtiene la ficha a través del aprendiz asociado a la asistencia.
         * Cadena: Attendance → Apprentice → ficha_id
         */
        if ($event->subjectType === Attendance::class) {

            // Carga solo los campos necesarios de la asistencia
            $attendance = Attendance::select(['id', 'apprentice_id'])
                ->whereKey($event->subjectId)
                ->first();

            // Si la asistencia no tiene aprendiz, no puede continuar
            if (!$attendance?->apprentice_id) {
                return;
            }

            // Carga el aprendiz con su perfil para obtener nombre y ficha
            $apprentice = Apprentice::with(['profile'])
                ->select(['id', 'ficha_id'])
                ->find($attendance->apprentice_id);

            // Si el aprendiz no tiene ficha asignada, no puede continuar
            if (!$apprentice?->ficha_id) {
                return;
            }

            $fichaId = $apprentice->ficha_id;

            // Construye el nombre completo del aprendiz para el mensaje
            $apprenticeName = $apprentice?->profile
                ? trim($apprentice->profile->first_name . ' ' . $apprentice->profile->last_name)
                : 'El aprendiz'; // Fallback si no tiene perfil
        }

        /**
         * CASO 2: RealClass creada.
         *
         * Obtiene la ficha a través de la cadena de relaciones:
         * RealClass → ScheduleSession → Schedule → FichaTerm → ficha_id
         */
        if ($event->subjectType === RealClass::class) {

            // Carga la clase real con todas las relaciones necesarias para llegar a la ficha
            $realClass = RealClass::with([
                'scheduleSession.schedule.fichaTerm'
            ])
                ->select(['id', 'schedule_session_id'])
                ->find($event->subjectId);

            // Navega la cadena de relaciones para obtener el ficha_id
            $fichaId = $realClass?->scheduleSession?->schedule?->fichaTerm?->ficha_id;

            // Si no se pudo determinar la ficha, no puede continuar
            if (!$fichaId) {
                return;
            }
        }

        // Obtiene la ficha con solo los campos necesarios
        $ficha = Ficha::select(['id', 'ficha_number', 'gestor_id'])->find($fichaId);

        // Si la ficha no tiene gestor asignado, no puede enviar notificación
        if (!$ficha?->gestor_id) {
            return;
        }

        // Verifica que el gestor asignado tenga efectivamente el rol correcto
        // Esto previene notificar a usuarios que ya no tienen ese rol
        $gestorId = User::role('Gestor de Fichas')
            ->whereKey($ficha->gestor_id)
            ->value('id');

        // Si el gestor no tiene el rol esperado, no hace nada
        if (!$gestorId) {
            return;
        }

        // Construye título y contenido según el tipo de recurso
        $title = $event->subjectType === Attendance::class
            ? 'Inasistencia registrada'
            : 'Clase real creada';

        $content = $event->subjectType === Attendance::class
            ? "Se registró una inasistencia de {$apprenticeName} para la ficha {$ficha->ficha_number}."
            : "Se registró una clase real para la ficha {$ficha->ficha_number}.";

        // Crea la notificación en la BD con relación polimórfica al recurso
        $notification = Notification::create([
            'notification_type_id' => $notificationType->id,
            'title'                => $title,
            'content'              => $content,
            'role_code'            => 'GESTOR_FICHAS',
            'modelable_type'       => $event->subjectType, // Tipo del modelo relacionado
            'modelable_id'         => $event->subjectId,   // ID del modelo relacionado
        ]);

        // Vincula la notificación con el gestor en la tabla pivot
        $notification->users()->syncWithoutDetaching([
            $gestorId => ['read_at' => null, 'role_code' => 'GESTOR_FICHAS'],
        ]);

        // Transmite la notificación en tiempo real al canal privado del gestor
        broadcast(new NotificationCreated($notification, [$gestorId], 'GESTOR_FICHAS'));
    }
}