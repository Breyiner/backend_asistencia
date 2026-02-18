<?php

namespace App\Services\EmailVerification;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Throwable;

/**
 * Servicio de lógica de negocio para la verificación de correo electrónico.
 *
 * Maneja dos flujos: verificar el email a través del enlace enviado al usuario,
 * y reenviar el enlace de verificación si no llegó o expiró.
 */
class EmailVerificationService
{
    /**
     * Verifica el email de un usuario a partir del ID y hash del enlace.
     *
     * El flujo de validación es secuencial y falla rápido:
     * 1. Verifica que el usuario exista.
     * 2. Valida que el hash del enlace corresponda al email del usuario.
     * 3. Verifica que el email no haya sido ya verificado previamente.
     *
     * @param  string  $id    ID del usuario extraído del enlace de verificación.
     * @param  string  $hash  Hash SHA1 del email, usado para validar la autenticidad del enlace.
     * @return array
     */
    public function verify(string $id, string $hash): array
    {
        $user = User::find($id);

        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado.'];
        }

        // Compara el hash del enlace con el SHA1 del email actual del usuario.
        // hash_equals() previene timing attacks al comparar cadenas en tiempo constante,
        // evitando que un atacante deduzca el hash correcto midiendo tiempos de respuesta.
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return ['error' => true, 'code' => 403, 'message' => 'Link inválido.'];
        }

        // Si ya fue verificado, retorna éxito sin volver a disparar el evento.
        // Esto hace el endpoint idempotente: verificar dos veces no causa errores.
        if ($user->hasVerifiedEmail()) {
            return ['error' => false, 'code' => 200, 'message' => 'Ya estaba verificado.'];
        }

        // Marca el email como verificado y dispara el evento Verified.
        // El evento puede ser escuchado para asignar roles, enviar bienvenida, etc.
        $user->markEmailAsVerified();
        event(new Verified($user));

        return ['error' => false, 'code' => 200, 'message' => 'Email verificado.'];
    }

    /**
     * Reenvía el enlace de verificación al email indicado.
     *
     * Por seguridad, siempre retorna el mismo mensaje genérico sin importar si el
     * usuario existe o no, ni si ya estaba verificado. Esto evita que un atacante
     * pueda enumerar qué emails están registrados en el sistema (user enumeration).
     *
     * Solo falla con error real si el envío del email lanza una excepción (ej: fallo del servidor SMTP).
     *
     * @param  array  $data  Debe contener el campo email.
     * @return array
     */
    public function resend(array $data): array
    {
        ['email' => $email] = $data;

        $user = User::where('email', $email)->first();

        // Usuario no encontrado: retorna el mensaje genérico sin revelar que no existe.
        if (!$user) {
            return ['error' => false, 'code' => 200, 'message' => 'Si el correo existe, se enviará un enlace de verificación.'];
        }

        // Ya verificado: retorna el mismo mensaje genérico para no revelar el estado.
        if ($user->hasVerifiedEmail()) {
            return ['error' => false, 'code' => 200, 'message' => 'Si el correo existe, se enviará un enlace de verificación.'];
        }

        try {
            $user->sendEmailVerificationNotification();
            return ['error' => false, 'code' => 200, 'message' => 'Si el correo existe, se enviará un enlace de verificación.'];
        } catch (Throwable $e) {
            // report() registra la excepción en los logs sin interrumpir la ejecución,
            // permitiendo depurar fallos del servidor SMTP sin exponer detalles al cliente.
            report($e);
            return ['error' => true, 'code' => 503, 'message' => 'No se pudo enviar el correo.'];
        }
    }
}