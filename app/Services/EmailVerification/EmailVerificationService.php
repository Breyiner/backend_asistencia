<?php
namespace App\Services\EmailVerification;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Throwable;

class EmailVerificationService
{
    public function verify(string $id, string $hash): array
    {
        $user = User::find($id);

        if (! $user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado.'];
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return ['error' => true, 'code' => 403, 'message' => 'Link inválido.'];
        }

        if ($user->hasVerifiedEmail()) {
            return ['error' => false, 'code' => 200, 'message' => 'Ya estaba verificado.'];
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return ['error' => false, 'code' => 200, 'message' => 'Email verificado.'];
    }

    public function resend(array $data): array
    {
        ['email' => $email] = $data;

        $user = User::where('email', $email)->first();

        if (!$user) {
            return ['error' => false, 'code' => 200, 'message' => 'Si el correo existe, se enviará un enlace de verificación.'];
        }

        if ($user->hasVerifiedEmail()) {
            return ['error' => false, 'code' => 200, 'message' => 'Si el correo existe, se enviará un enlace de verificación.'];
        }

        try {
            $user->sendEmailVerificationNotification();
            return ['error' => false, 'code' => 200, 'message' => 'Si el correo existe, se enviará un enlace de verificación.'];
        } catch (Throwable $e) {
            report($e);
            return ['error' => true, 'code' => 503, 'message' => 'No se pudo enviar el correo.'];
        }
    }
}
