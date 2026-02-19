<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notificación personalizada para restablecer contraseña.
 *
 * Sobrescribe la notificación por defecto de Laravel para
 * redirigir al frontend en lugar del backend, y personalizar
 * el contenido del email.
 *
 * Diferencia con la notificación por defecto de Laravel:
 * - La URL apunta al frontend (Vue/React) en lugar de una ruta de Laravel
 * - Texto y asunto en español
 * - Formato del email personalizado con emojis y nombre de la app
 *
 * Flujo de restablecimiento:
 * 1. Usuario solicita reset en /forgot-password
 * 2. Laravel genera token y llama a esta notificación
 * 3. Email con enlace al frontend: /reset-password?token=...&email=...
 * 4. Frontend envía token + nueva contraseña al backend
 * 5. Backend valida token y actualiza contraseña
 *
 * Implementa Queueable para envío en cola (segundo plano).
 *
 * Registro en User model:
 * public function sendPasswordResetNotification($token): void {
 *     $this->notify(new CustomResetPassword($token, $this->email));
 * }
 */
class CustomResetPassword extends Notification
{
    // Permite enviar la notificación via cola
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * Usa constructor property promotion de PHP 8.
     *
     * @param string $token Token único de restablecimiento generado por Laravel
     * @param string $email Email del usuario que solicitó el reset
     */
    public function __construct(
        public string $token,  // Token para validar el reset en el backend
        public string $email   // Email para pre-llenar el formulario del frontend
    ) {}

    /**
     * Define los canales de envío de la notificación.
     *
     * Solo usa el canal de email (mail).
     * Podría extenderse con 'database', 'sms', etc.
     *
     * @param mixed $notifiable Usuario que recibe la notificación
     * @return array Canales de envío
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye el mensaje de email para el reset de contraseña.
     *
     * Genera la URL del frontend con el token y email como query params.
     * El frontend usará estos params para el formulario de nueva contraseña.
     *
     * @param mixed $notifiable Usuario que recibe la notificación
     * @return MailMessage Mensaje de email configurado
     */
    public function toMail($notifiable): MailMessage
    {
        // Obtiene la URL base del frontend desde configuración
        // Fallback a localhost:5173 (Vite) para desarrollo
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        // Construye la URL completa del frontend para el reset
        // urlencode() en el email previene problemas con caracteres especiales
        $resetUrl = $frontendUrl
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($this->email);

        return (new MailMessage)
            // Asunto con emoji y nombre de la aplicación
            ->subject('🔐 Restablece tu contraseña - ' . config('app.name'))

            // Saludo inicial
            ->greeting('¡Hola!')

            // Primera línea explicativa
            ->line('Recibiste este correo porque solicitaste restablecer tu contraseña.')

            // Botón CTA con la URL del frontend
            ->action('Restablecer contraseña ahora', $resetUrl)

            // Advertencia de expiración del enlace (60 minutos = config de Laravel)
            ->line('El enlace expira en <strong>60 minutos</strong>.')

            // Mensaje de seguridad para evitar preocupaciones
            ->line('Si no solicitaste esto, puedes ignorar este mensaje.')

            // Firma del email con nombre de la app
            ->salutation('Saludos, el equipo de ' . config('app.name'));
    }
}