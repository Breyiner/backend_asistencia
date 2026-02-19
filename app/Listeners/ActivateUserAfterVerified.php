<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Listener que activa un usuario cuando verifica su email.
 *
 * Escucha el evento Verified de Laravel (disparado automáticamente
 * cuando el usuario hace click en el enlace de verificación de email).
 *
 * Flujo de registro y activación:
 * 1. Usuario se registra → status_id = 2 (pendiente/inactivo)
 * 2. Sistema envía email de verificación
 * 3. Usuario hace click en el enlace
 * 4. Laravel dispara Verified
 * 5. Este listener cambia status_id a 1 (activo)
 * 6. Usuario puede iniciar sesión
 *
 * NO implementa ShouldQueue para que la activación sea inmediata
 * y el usuario pueda iniciar sesión al instante.
 *
 * Registro en EventServiceProvider:
 * Verified::class => [
 *     ActivateUserAfterVerified::class,
 * ]
 */
class ActivateUserAfterVerified
{
    /**
     * Crea una nueva instancia del listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Maneja el evento de verificación de email.
     *
     * Cambia el status_id del usuario a 1 (activo)
     * para permitirle acceder a la aplicación.
     *
     * @param Verified $event Evento con el usuario que verificó su email
     */
    public function handle(Verified $event): void
    {
        $user = $event->user;

        // Activa el usuario cambiando su estado a activo (status_id = 1)
        $user->update(['status_id' => 1]);
    }
}