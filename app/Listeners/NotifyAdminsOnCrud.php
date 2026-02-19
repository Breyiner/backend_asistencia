<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\ResourceChanged;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\RealClass;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener que notifica a todos los administradores
 * cuando se crea, actualiza o elimina un recurso en el sistema.
 *
 * Escucha el evento ResourceChanged y envía notificaciones
 * en tiempo real a todos los usuarios con rol Administrador.
 *
 * Exclusiones:
 * - Attendance: tiene su propio listener (NotifyGestorOnAttendanceOrRealClass)
 * - RealClass: tiene su propio listener
 *
 * NO implementa ShouldQueue para que las notificaciones sean inmediatas.
 * Si se necesitara mayor performance, podría implementarse ShouldQueue.
 *
 * Registro en EventServiceProvider:
 * ResourceChanged::class => [
 *     NotifyAdminsOnCrud::class,
 * ]
 */
class NotifyAdminsOnCrud
{
    /**
     * Modelos excluidos de las notificaciones a administradores.
     *
     * Estos modelos tienen listeners propios especializados
     * y no deben generar notificaciones genéricas de admin.
     *
     * @var array
     */
    protected array $excludedSubjects = [
        Attendance::class,
        RealClass::class,
    ];

    /**
     * Maneja el evento ResourceChanged.
     *
     * Proceso:
     * 1. Registra en log para debugging
     * 2. Verifica que el subject no esté excluido
     * 3. Obtiene todos los administradores
     * 4. Determina el tipo de notificación según la acción
     * 5. Crea la notificación y la vincula a todos los admins
     * 6. Transmite via WebSocket a todos los admins
     *
     * @param ResourceChanged $event Evento con acción, tipo, ID y label del recurso
     */
    public function handle(ResourceChanged $event): void
    {

        // Ignora modelos que tienen sus propios listeners especializados
        if (in_array($event->subjectType, $this->excludedSubjects, true)) {
            return;
        }

        // Obtiene IDs de todos los usuarios con rol Administrador
        $adminIds = User::role('Administrador')->pluck('id')->all();

        // Si no hay administradores en el sistema, no hace nada
        if (empty($adminIds)) {
            return;
        }

        // Mapea la acción del evento a la clave del tipo de notificación
        // Las acciones en el sistema son en español ('crear', 'actualizar', 'eliminar')
        $typeKey = match ($event->action) {
            'crear'       => 'resource_created',
            'actualizar'  => 'resource_updated',
            'eliminar'    => 'resource_deleted',
            default       => 'resource_updated', // Fallback para acciones no mapeadas
        };

        // Obtiene solo el ID del tipo de notificación (más eficiente que first())
        $notificationTypeId = NotificationType::where('key', $typeKey)->value('id');

        // Si no existe el tipo de notificación configurado, no hace nada
        if (!$notificationTypeId) {
            return;
        }

        // Usa el label del evento si existe, si no usa el nombre base de la clase
        // Ejemplo: 'App\Models\User' → 'User'
        $label = $event->subjectLabel ?? class_basename($event->subjectType);

        // Mapea la acción a texto en español para el mensaje
        $actionText = match ($event->action) {
            'crear'      => 'creado',
            'actualizar' => 'actualizado',
            'eliminar'   => 'eliminado',
            default      => 'modificado',
        };

        // Crea la notificación con relación polimórfica al recurso afectado
        $notification = Notification::create([
            'notification_type_id' => $notificationTypeId,
            'title'                => "Se ha {$actionText} un registro de {$label}",
            'content'              => "Se ha {$actionText} el registro de {$label} con ID {$event->subjectId}.",
            'modelable_type'       => $event->subjectType, // Clase del modelo
            'modelable_id'         => $event->subjectId,   // ID del registro
        ]);

        // Construye el payload para la tabla pivot
        // Vincula la notificación a cada administrador con read_at = null (no leída)
        $payload = [];
        foreach ($adminIds as $id) {
            $payload[$id] = ['read_at' => null, 'role_code' => 'ADMIN'];
        }

        // Vincula la notificación a todos los admins sin eliminar vínculos existentes
        $notification->users()->syncWithoutDetaching($payload);

        // Transmite la notificación en tiempo real a los canales privados de todos los admins
        broadcast(new NotificationCreated($notification, $adminIds, 'ADMIN'));
    }
}