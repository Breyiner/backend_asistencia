<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Mail\UserCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Listener que envía email de bienvenida cuando se crea un usuario.
 *
 * Escucha el evento UserCreated y envía un correo electrónico
 * al nuevo usuario con sus datos de acceso o instrucciones.
 *
 * Implementa ShouldQueue para procesar el envío de email
 * en segundo plano (cola), evitando que el request HTTP
 * espere a que el email sea enviado.
 *
 * Ventajas de usar cola para emails:
 * - El request responde inmediatamente al cliente
 * - Si el servidor de email falla, la cola reintenta automáticamente
 * - No bloquea el proceso principal
 *
 * Requiere configuración de queue driver en .env:
 * QUEUE_CONNECTION=database (o redis, sqs, etc.)
 *
 * Para procesar la cola:
 * php artisan queue:work
 *
 * Registro en EventServiceProvider:
 * UserCreated::class => [
 *     SendEmailUserCreated::class,
 * ]
 */
class SendEmailUserCreated implements ShouldQueue
{
    // Proporciona métodos para manejo de reintentos y fallos de cola
    use InteractsWithQueue;

    /**
     * Crea una nueva instancia del listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Maneja el evento UserCreated.
     *
     * Obtiene los datos del usuario del evento y envía
     * el email de bienvenida usando UserCreatedMail.
     *
     * Se ejecuta en background via queue worker.
     *
     * @param UserCreated $event Evento con datos del usuario creado
     */
    public function handle(UserCreated $event): void
    {
        // Obtiene el array de datos del usuario desde el evento
        $user = $event->user;

        // Envía el email al usuario usando el Mailable UserCreatedMail
        // Mail::to() acepta email string o modelo con propiedad email
        Mail::to($user['email'])->send(new UserCreatedMail($user));
    }
}