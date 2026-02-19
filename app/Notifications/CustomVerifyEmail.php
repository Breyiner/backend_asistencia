<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notificación personalizada para verificación de email.
 *
 * Extiende la notificación base de Laravel (VerifyEmail) para:
 * - Redirigir al frontend en lugar de usar rutas del backend
 * - Usar una vista Blade personalizada en lugar del template por defecto
 * - Personalizar el asunto del email
 *
 * Diferencia con VerifyEmail base de Laravel:
 * - La URL apunta al frontend: /verificar-email?verify_url=...
 * - El frontend recibe la URL de verificación real y la procesa
 * - Usa vista Blade personalizada (emails.verify-email)
 *
 * Flujo de verificación:
 * 1. Usuario se registra → sistema envía este email
 * 2. Usuario hace click en el enlace del email
 * 3. Frontend recibe verify_url en query param
 * 4. Frontend hace petición al backend con verify_url
 * 5. Backend procesa la verificación y activa el usuario
 * 6. Listener ActivateUserAfterVerified cambia status_id a 1
 *
 * Registro en User model:
 * public function sendEmailVerificationNotification(): void {
 *     $this->notify(new CustomVerifyEmail());
 * }
 */
class CustomVerifyEmail extends VerifyEmailBase
{
    /**
     * Construye el mensaje de email para verificación.
     *
     * Sobrescribe el método de la clase base para personalizar:
     * - La URL: redirige al frontend con la URL de verificación como query param
     * - La vista: usa template Blade personalizado
     * - El asunto
     *
     * @param mixed $notifiable Usuario que debe verificar su email
     * @return MailMessage Mensaje de email configurado
     */
    public function toMail($notifiable)
    {
        // Genera la URL de verificación firmada de Laravel
        // Este método está en VerifyEmailBase y genera una URL con firma y expiración
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            // Asunto personalizado en español con nombre del sistema
            ->subject('Verifica tu correo - Sistema de Asistencias')

            // Renderiza vista Blade personalizada en lugar del template por defecto
            // La vista recibe:
            // - $url: URL del frontend que procesará la verificación
            // - $user: modelo del usuario para personalizar el email
            ->view('emails.verify-email', [
                // URL del frontend con la URL de verificación de Laravel encodada
                // El frontend extrae verify_url y hace la petición al backend
                'url'  => "http://localhost:5173/verificar-email?verify_url=" . urlencode($verificationUrl),

                // Modelo del usuario para personalizar el email (nombre, etc.)
                'user' => $notifiable,
            ]);
    }
}