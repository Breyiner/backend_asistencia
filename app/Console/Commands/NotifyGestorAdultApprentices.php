<?php

namespace App\Console\Commands;

use App\Events\NotificationCreated;
use App\Models\Apprentice;
use App\Models\Ficha;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Comando Artisan para notificar al gestor de fichas
 * cuando un aprendiz cumple 18 años.
 *
 * Este comando está diseñado para ejecutarse diariamente
 * mediante el scheduler de Laravel (cron job).
 *
 * Flujo del comando:
 * 1. Calcula la fecha de nacimiento de quienes cumplen 18 años hoy
 * 2. Busca aprendices que nacieron exactamente hace 18 años
 * 3. Para cada aprendiz, verifica que no haya sido notificado hoy
 * 4. Busca al gestor de la ficha del aprendiz
 * 5. Crea la notificación y la transmite por WebSocket
 *
 * Registro en el scheduler (app/Console/Kernel.php o routes/console.php):
 * Schedule::command('apprentices:notify-adults')->dailyAt('08:00');
 *
 * Ejecución manual:
 * php artisan apprentices:notify-adults
 */
class NotifyGestorAdultApprentices extends Command
{
    /**
     * Firma del comando Artisan.
     *
     * Define el nombre con el que se invoca:
     * php artisan apprentices:notify-adults
     *
     * @var string
     */
    protected $signature = 'apprentices:notify-adults';

    /**
     * Descripción del comando.
     *
     * Se muestra al ejecutar: php artisan list
     *
     * @var string
     */
    protected $description = 'Notifica al gestor cuando un aprendiz cumple 18 años (diario).';

    /**
     * Ejecuta la lógica principal del comando.
     *
     * Proceso completo:
     * 1. Calcula fechas relevantes para hoy
     * 2. Verifica que exista el tipo de notificación configurado
     * 3. Busca aprendices que cumplen 18 años hoy
     * 4. Por cada aprendiz:
     *    a. Verifica que tenga ficha asignada
     *    b. Verifica que no haya notificación duplicada hoy
     *    c. Obtiene la ficha y su gestor
     *    d. Crea y transmite la notificación
     *
     * @return int Código de salida (self::SUCCESS = 0, self::FAILURE = 1)
     */
    public function handle(): int
    {
        // Fecha actual en formato YYYY-MM-DD
        // Usada para verificar duplicados de notificaciones del día
        $adultDate = now()->toDateString();

        // Fecha de nacimiento de quienes cumplen exactamente 18 años hoy
        // subYears(18) resta 18 años a la fecha actual
        $birthDate = now()->subYears(18)->toDateString();

        // Busca el tipo de notificación configurado en la BD
        // La clave 'apprentice_became_adult' debe existir en notification_types
        $notificationType = NotificationType::where('key', 'apprentice_became_adult')->first();

        // Si no existe el tipo de notificación, termina sin error
        // Esto permite que el comando funcione aunque no esté configurado
        if (!$notificationType) {
            return self::SUCCESS;
        }

        // Busca aprendices cuyo perfil tiene fecha de nacimiento = $birthDate
        // Solo carga los campos necesarios (select) para optimizar memoria
        // Eager loading del profile para evitar N+1 queries
        $apprentices = Apprentice::query()
            ->select(['id', 'ficha_id'])                    // Solo ID y ficha_id
            ->with([
                // Carga solo los campos del perfil que se necesitan
                'profile:id,user_id,first_name,last_name,birth_date'
            ])
            // Filtra por fecha de nacimiento exacta en la relación profile
            ->whereHas('profile', function ($q) use ($birthDate) {
                $q->whereDate('birth_date', $birthDate);
            })
            ->get();

        // Itera sobre cada aprendiz que cumple 18 hoy
        foreach ($apprentices as $apprentice) {

            // Si el aprendiz no tiene ficha asignada, salta al siguiente
            if (!$apprentice->ficha_id) continue;

            // Verifica si ya se envió esta notificación hoy para este aprendiz
            // Previene notificaciones duplicadas si el comando se ejecuta varias veces
            $alreadyNotified = Notification::query()
                ->where('notification_type_id', $notificationType->id)
                ->where('modelable_type', Apprentice::class)  // Polimorfismo: tipo del modelo
                ->where('modelable_id', $apprentice->id)      // Polimorfismo: ID del modelo
                ->whereDate('created_at', $adultDate)         // Solo hoy
                ->exists();                                    // Solo verifica existencia

            // Si ya fue notificado hoy, salta al siguiente
            if ($alreadyNotified) continue;

            // Busca la ficha del aprendiz con solo los campos necesarios
            $ficha = Ficha::select(['id', 'ficha_number', 'gestor_id'])->find($apprentice->ficha_id);

            // Si la ficha no tiene gestor asignado, salta al siguiente
            if (!$ficha?->gestor_id) continue;

            // Verifica que el gestor tenga el rol correcto
            // Busca un usuario con ID = gestor_id que tenga el rol 'Gestor de Fichas'
            // value('id') retorna solo el ID o null si no existe
            $gestorId = User::role('Gestor de Fichas')
                ->whereKey($ficha->gestor_id)   // whereKey = where primary key
                ->value('id');

            // Si no se encontró el gestor con el rol correcto, salta al siguiente
            if (!$gestorId) continue;

            // Construye el nombre completo del aprendiz
            // trim() elimina espacios extra si falta nombre o apellido
            $apprenticeName = $apprentice->profile
                ? trim($apprentice->profile->first_name . ' ' . $apprentice->profile->last_name)
                : 'El aprendiz'; // Fallback si no tiene perfil

            // Crea el registro de notificación en la base de datos
            $notification = Notification::create([
                'notification_type_id' => $notificationType->id,
                'title'                => 'Aprendiz cumple mayoría de edad',
                'content'              => "El aprendiz {$apprenticeName} cumplió 18 años (ficha {$ficha->ficha_number}).",
                'role_code'            => 'GESTOR_FICHAS',   // Rol destinatario
                'modelable_type'       => Apprentice::class,  // Relación polimórfica: tipo
                'modelable_id'         => $apprentice->id,    // Relación polimórfica: ID
            ]);

            // Vincula la notificación con el gestor en la tabla pivot
            // syncWithoutDetaching: agrega sin eliminar vínculos existentes
            // El array define los valores de la tabla pivot:
            //   - read_at: null = no leída
            //   - role_code: rol del destinatario
            $notification->users()->syncWithoutDetaching([
                $gestorId => ['read_at' => null, 'role_code' => 'GESTOR_FICHAS'],
            ]);

            // Transmite la notificación via WebSocket en tiempo real
            // El evento NotificationCreated enviará la notificación al canal privado del gestor
            broadcast(new NotificationCreated($notification, [$gestorId], 'GESTOR_FICHAS'));
        }

        // Retorna código de éxito (0)
        return self::SUCCESS;
    }
}