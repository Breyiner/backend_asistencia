<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para notificar al usuario cuando su cuenta es creada.
 *
 * Se envía cuando un administrador crea un nuevo usuario en el sistema.
 * Incluye los datos de acceso del usuario (email y contraseña temporal)
 * para que pueda iniciar sesión.
 *
 * Implementa Queueable para enviarse en segundo plano via cola,
 * evitando que el request HTTP espere al servidor de correo.
 *
 * Vista asociada: resources/views/emails/user_created.blade.php
 * La vista tiene acceso a todas las propiedades públicas de la clase.
 *
 * Uso:
 * Mail::to($user->email)->send(new UserCreatedMail($userData));
 *
 * O en cola:
 * Mail::to($user->email)->queue(new UserCreatedMail($userData));
 */
class UserCreatedMail extends Mailable
{
    // Permite enviar el Mailable via cola (queue)
    use Queueable;

    // Permite serializar modelos Eloquent si se pasan como parámetros
    use SerializesModels;

    /**
     * Nombre del usuario destinatario.
     * Accesible en la vista como $first_name.
     *
     * @var string
     */
    public $first_name;

    /**
     * Apellido del usuario destinatario.
     * Accesible en la vista como $last_name.
     *
     * @var string
     */
    public $last_name;

    /**
     * Email del usuario destinatario.
     * Accesible en la vista como $email.
     *
     * @var string
     */
    public $email;

    /**
     * Contraseña temporal del usuario.
     * Se muestra en el email para que el usuario pueda iniciar sesión.
     * Accesible en la vista como $password.
     *
     * @var string|null
     */
    public $password;

    /**
     * Fecha de creación de la cuenta.
     * Accesible en la vista como $created_at.
     *
     * @var string
     */
    public $created_at;

    /**
     * Crea una nueva instancia del Mailable.
     *
     * Extrae y asigna los datos del usuario a propiedades públicas
     * para que estén disponibles en la vista del email.
     *
     * @param array $user Datos del usuario recién creado
     * @param string $user['first_name'] Nombre del usuario
     * @param string $user['last_name'] Apellido del usuario
     * @param string $user['email'] Email del usuario
     * @param string|null $user['password'] Contraseña temporal
     * @param string $user['created_at'] Fecha de creación
     */
    public function __construct(array $user)
    {
        $this->first_name = $user['first_name'];
        $this->last_name  = $user['last_name'];
        $this->email      = $user['email'];
        $this->password   = $user['password'];
        $this->created_at = $user['created_at'];
    }

    /**
     * Define el sobre del mensaje (asunto y metadatos).
     *
     * @return Envelope Configuración del sobre del email
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Asunto del email que verá el destinatario
            subject: 'Notificación de Usuario Registrado',
        );
    }

    /**
     * Define el contenido del mensaje (vista a renderizar).
     *
     * La vista tiene acceso a todas las propiedades públicas:
     * $first_name, $last_name, $email, $password, $created_at
     *
     * @return Content Configuración del contenido del email
     */
    public function content(): Content
    {
        return new Content(
            // Vista Blade que se renderizará como cuerpo del email
            view: 'emails.user_created',
        );
    }

    /**
     * Define los archivos adjuntos del mensaje.
     *
     * Sin adjuntos para este email.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}