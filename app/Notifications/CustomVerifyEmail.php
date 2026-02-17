<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmailBase
{
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        
        return (new MailMessage)
            ->subject('Verifica tu correo - Sistema de Asistencias')
            ->view('emails.verify-email', [
                'url' => "http://localhost:5173/verificar-email?verify_url=" . urlencode($verificationUrl),
                'user' => $notifiable
            ]);
    }
}