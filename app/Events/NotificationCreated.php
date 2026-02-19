<?php

namespace App\Events;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de nueva notificación para broadcasting en tiempo real.
 *
 * Este evento implementa ShouldBroadcastNow (sin cola, instantáneo)
 * para transmitir notificaciones via WebSocket (Laravel Reverb) a los
 * canales privados de los usuarios destinatarios.
 *
 * Diferencia entre ShouldBroadcast y ShouldBroadcastNow:
 * - ShouldBroadcast: se despacha via cola (asíncrono)
 * - ShouldBroadcastNow: se despacha inmediatamente (síncrono)
 *
 * Flujo de uso:
 * 1. Se crea una Notification en la BD
 * 2. Se dispara este evento con la notificación y sus destinatarios
 * 3. Laravel Reverb transmite el evento a los canales privados
 * 4. El frontend recibe el evento via Laravel Echo
 *
 * Uso:
 * broadcast(new NotificationCreated($notification, [1, 2, 3], 'INSTRUCTOR'));
 *
 * Frontend (JavaScript/Echo):
 * echo.private(`users.${userId}`)
 *     .listen('.notification.created', (payload) => {
 *         console.log(payload);
 *     });
 */
class NotificationCreated implements ShouldBroadcastNow
{
    // Permite serializar modelos Eloquent para broadcasting
    use SerializesModels;

    /**
     * Crea una nueva instancia del evento.
     *
     * Usa constructor property promotion de PHP 8.
     *
     * @param Notification $notification Notificación creada en la BD
     * @param array $userIds Array de IDs de usuarios destinatarios
     * @param string $roleCode Código del rol destinatario (ej: 'INSTRUCTOR')
     */
    public function __construct(
        public Notification $notification, // Notificación a transmitir
        public array $userIds,             // IDs de usuarios que recibirán la notificación
        public string $roleCode,           // Código del rol para filtrado en el frontend
    ) {}

    /**
     * Define los canales en los que se transmite el evento.
     *
     * Crea un canal privado por cada usuario destinatario.
     * Los canales privados requieren autenticación del frontend.
     *
     * Formato del canal: "users.{userId}"
     * Ejemplo: "users.1", "users.5", "users.12"
     *
     * @return array Array de instancias PrivateChannel
     */
    public function broadcastOn(): array
    {
        // Convierte cada userId en un PrivateChannel
        // collect() crea una colección para usar map()
        return collect($this->userIds)
            ->map(fn ($id) => new PrivateChannel("users.{$id}"))
            ->all(); // Convierte colección a array
    }

    /**
     * Define el nombre del evento en el frontend.
     *
     * Por defecto, Laravel usa el nombre completo de la clase.
     * Este método lo personaliza a '.notification.created'.
     *
     * El punto (.) al inicio es convención de Laravel Echo
     * para eventos personalizados (no generados por Laravel).
     *
     * Frontend:
     * channel.listen('.notification.created', callback)
     *
     * @return string Nombre del evento para el cliente
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Define los datos que se transmiten con el evento.
     *
     * Construye el payload que recibirá el frontend.
     * Incluye todos los datos necesarios para mostrar la notificación
     * sin necesidad de hacer peticiones adicionales al servidor.
     *
     * @return array Datos del evento para el frontend
     */
    public function broadcastWith(): array
    {
        // Genera fecha humanizada (ej: "hace 5 minutos")
        $humanized = Carbon::parse($this->notification->created_at)
            ->diffForHumans();

        return [
            // ID de la notificación en la BD
            'id'              => $this->notification->id,

            // Título de la notificación
            'title'           => $this->notification->title,

            // Contenido/mensaje de la notificación
            'content'         => $this->notification->content,

            // ID del tipo de notificación
            'type'            => $this->notification->notification_type_id,

            // Código del rol destinatario
            // El frontend lo usa para filtrar notificaciones por rol activo
            'role_code'       => $this->roleCode,

            // Siempre null en notificaciones nuevas (no leídas)
            'read_at'         => null,

            // Timestamp en formato ISO 8601
            'created_at'      => $this->notification->created_at->toISOString(),

            // Fecha humanizada para mostrar al usuario
            'created_at_human' => $humanized,
        ];
    }
}