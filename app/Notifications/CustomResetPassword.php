<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends Notification
{
    use Queueable;

    public function __construct(public string $token, public string $email) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->email);
        
        return (new MailMessage)
            ->subject('🔐 Restablece tu contraseña - ' . config('app.name'))
            ->greeting('¡Hola!')
            ->line('Recibiste este correo porque solicitaste restablecer tu contraseña.')
            ->action('Restablecer contraseña ahora', $resetUrl)
            ->line('El enlace expira en <strong>60 minutos</strong>.')
            ->line('Si no solicitaste esto, puedes ignorar este mensaje.')
            ->salutation('Saludos, el equipo de ' . config('app.name'));
    }
}
