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

    public function resend(MustVerifyEmail $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return ['error' => true, 'code' => 409, 'message' => 'El email ya está verificado.'];
        }

        try {
            $user->sendEmailVerificationNotification();
            return ['error' => false, 'code' => 200, 'message' => 'Correo reenviado.'];
        } catch (Throwable $e) {
            report($e);
            return ['error' => true, 'code' => 503, 'message' => 'No se pudo enviar el correo.'];
        }
    }
}
