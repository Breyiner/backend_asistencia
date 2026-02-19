<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notificación personalizada para verificación de email en cola.
 *
 * Implementa ShouldQueue: se ejecuta asíncronamente en cola 'default'
 * 
 * Extiende VerifyEmailBase con:
 * - Redirección al frontend (/verificar-email?verify_url=...)
 * - Vista Blade personalizada (emails.verify-email)
 * - Procesamiento asíncrono (no bloquea registro)
 *
 * Ventajas de cola:
 * - Registro instantáneo (email en background)
 * - Alta disponibilidad (no falla si SMTP offline)
 * - Escalable (múltiples workers procesan emails)
 * - Retry automático (3 intentos si SMTP falla)
 *
 * Configuración cola (.env):
 * QUEUE_CONNECTION=database/redis
 * MAIL_MAILER=smtp/mailgun/ses
 *
 * Flujo con cola:
 * 1. Usuario registra → $user->notify() → JOB en cola
 * 2. Worker: php artisan queue:work procesa email
 * 3. Usuario hace click → frontend → backend verifica
 *
 * Registro en User model:
 * public function sendEmailVerificationNotification(): void {
 *     $this->notify(new CustomVerifyEmail());
 * }
 */
class CustomVerifyEmail extends VerifyEmailBase implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [30, 60, 120];

    /**
     * Construye mensaje de email para verificación.
     * 
     * Sobrescribe VerifyEmailBase para:
     * - URL frontend con verify_url encodada
     * - Vista Blade personalizada
     * - Asunto localizado
     * 
     * @param mixed $notifiable Usuario receptor (User model)
     * @return MailMessage Email listo para enviar
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifica tu correo - Sistema de Asistencias')
            ->view('emails.verify-email', [
                'url' => config('app.frontend_url') . "/verificar-email?verify_url=" . urlencode($verificationUrl),
                'user' => $notifiable,
            ]);
    }

    /**
     * Personalización: cola específica (opcional)
     * 
     * Por defecto usa 'default'. Puedes crear cola dedicada:
     * QUEUE_CONNECTION=redis → emails: alta prioridad
     */
    public function queue($queue = 'default')
    {
        return $queue;
    }

    /**
     * Manejo de fallos en cola (opcional)
     * 
     * Se ejecuta si supera $tries (3 intentos)
     */
    public function failed(\Exception $exception)
    {
        Log::error("Email verificación falló: " . $exception->getMessage());
    }
}
