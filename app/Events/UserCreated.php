<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se crea un nuevo usuario en el sistema.
 *
 * Este evento NO implementa ShouldBroadcast, por lo que no se transmite
 * via WebSocket. Es un evento interno del sistema para comunicación
 * entre capas de la aplicación.
 *
 * Propósito típico:
 * - Enviar email de bienvenida al nuevo usuario
 * - Asignar recursos por defecto
 * - Registrar en log de auditoría
 *
 * Uso:
 * event(new UserCreated($userDataArray));
 *
 * O con el helper:
 * UserCreated::dispatch($userDataArray);
 *
 * Listener ejemplo (en EventServiceProvider):
 * UserCreated::class => [
 *     SendWelcomeEmail::class,
 *     AssignDefaultPermissions::class,
 * ]
 */
class UserCreated
{
    // Permite disparar el evento: UserCreated::dispatch($data)
    use Dispatchable;

    // Permite serializar modelos si se usa en colas
    use SerializesModels;

    /**
     * Datos del usuario creado.
     *
     * Se almacena como array en lugar de modelo User
     * para mayor flexibilidad (puede incluir datos relacionados
     * como perfil, roles, etc.).
     *
     * @var array
     */
    public $user;

    /**
     * Crea una nueva instancia del evento.
     *
     * Recibe un array con los datos del usuario en lugar del
     * modelo Eloquent, permitiendo incluir datos adicionales
     * que no están en el modelo (como contraseña temporal,
     * datos del perfil, etc.).
     *
     * @param array $dataUser Array con datos del usuario recién creado
     */
    public function __construct(array $dataUser)
    {
        // Guarda los datos del usuario para que los listeners los accedan
        $this->user = $dataUser;
    }
}