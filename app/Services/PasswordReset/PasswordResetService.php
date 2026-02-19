<?php

namespace App\Services\PasswordReset;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio de lógica de negocio para el restablecimiento de contraseñas.
 *
 * Utiliza el broker de passwords nativo de Laravel (Password facade), que gestiona
 * internamente la tabla password_reset_tokens, el envío de correo y la validación
 * del token. Este servicio no construye tokens manualmente.
 *
 * Flujo completo:
 *   1. forgot()  → el usuario ingresa su documento → se envía enlace al correo.
 *   2. reset()   → el usuario llega desde el enlace con token → se cambia la contraseña.
 */
class PasswordResetService
{
    /**
     * Envía el enlace de restablecimiento al correo asociado al documento del usuario.
     *
     * Por seguridad, retorna el mismo mensaje exitoso tanto si el documento existe
     * como si no, evitando enumerar usuarios registrados en el sistema (security by obscurity).
     *
     * @param  array  $data  Datos validados (document_number requerido).
     * @return array
     */
    public function forgot(array $data): array
    {
        // Desestructuración directa del array para mayor legibilidad.
        ['document_number' => $documentNumber] = $data;

        $user = User::where('document_number', $documentNumber)->first();

        // Si el usuario no existe, retorna éxito genérico para no revelar si el documento está registrado.
        if (!$user) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'Si el número de documento existe, se envió el enlace.',
                'data' => []
            ];
        }

        try {
            // Password::sendResetLink() busca el usuario por email, genera el token,
            // lo almacena en password_reset_tokens y envía el correo de notificación.
            $status = Password::sendResetLink(['email' => $user->email]);

            // RESET_LINK_SENT: constante 'passwords.sent' → el correo fue encolado correctamente.
            return $status === Password::RESET_LINK_SENT
                ? ['error' => false, 'code' => 200, 'message' => 'Enlace enviado al correo.', 'data' => []]
                : ['error' => true, 'code' => 500, 'message' => 'Error enviando correo.', 'data' => []];

        } catch (Throwable $e) {
            // report() registra el error en los logs sin detener la ejecución.
            report($e);
            return ['error' => true, 'code' => 503, 'message' => 'Error de servidor.', 'data' => []];
        }
    }

    /**
     * Valida el token y actualiza la contraseña del usuario.
     *
     * Password::reset() verifica internamente que el token sea válido, no haya expirado
     * (config password.expire) y que el email coincida con el registrado en el token.
     * Si todo es correcto, ejecuta el callback y elimina el token usado.
     *
     * @param  array  $data  Datos validados:
     *                       - email (requerido, puede venir como query param desde el enlace)
     *                       - password (requerido)
     *                       - password_confirmation (requerido)
     *                       - token (requerido, viene en la URL del enlace de correo)
     * @return array
     */
    public function reset(array $data): array
    {
        // Fallback: si el email no viene en el body pero sí como query param en la URL del enlace.
        // Ocurre cuando el formulario de reset no incluye el email como campo hidden.
        if (!isset($data['email']) && request()->query('email')) {
            $data['email'] = request()->query('email');
        }

        try {
            // Password::reset() orquesta la validación del token + callback de actualización.
            $status = Password::reset($data, function (User $user, string $password) {
                // Actualiza la contraseña; el hashing se aplica automáticamente si el modelo
                // tiene 'password' en su cast o usa el mutator de Bcrypt/Argon2.
                $user->password = $password;

                // Rota el remember_token para invalidar sesiones "recuérdame" activas
                // y forzar re-autenticación tras el cambio de contraseña.
                $user->{$user->getRememberTokenName()} = Str::random(60);

                $user->save();
            });

            // PASSWORD_RESET: constante 'passwords.reset' → contraseña actualizada y token eliminado.
            return $status === Password::PASSWORD_RESET
                ? ['error' => false, 'code' => 200, 'message' => '¡Contraseña cambiada!', 'data' => []]
                : ['error' => true, 'code' => 400, 'message' => 'Token inválido o expirado.', 'data' => []];

        } catch (Throwable $e) {
            report($e);
            return ['error' => true, 'code' => 400, 'message' => 'Error procesando solicitud.', 'data' => []];
        }
    }
}
