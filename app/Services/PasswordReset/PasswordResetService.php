<?php

namespace App\Services\PasswordReset;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetService
{
    public function forgot(array $data): array
    {
        ['document_number' => $documentNumber] = $data;

        $user = User::where('document_number', $documentNumber)->first();

        if (!$user) {
            return [
                'error' => false, 
                'code' => 200, 
                'message' => 'Si el número de documento existe, se envió el enlace.', 
                'data' => []
            ];
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
            
            return $status === Password::RESET_LINK_SENT
                ? ['error' => false, 'code' => 200, 'message' => 'Enlace enviado al correo.', 'data' => []]
                : ['error' => true, 'code' => 500, 'message' => 'Error enviando correo.', 'data' => []];
        } catch (Throwable $e) {
            report($e);
            return ['error' => true, 'code' => 503, 'message' => 'Error de servidor.', 'data' => []];
        }
    }

    public function reset(array $data): array
    {
        if (!isset($data['email']) && request()->query('email')) {
            $data['email'] = request()->query('email');
        }
        
        try {
            $status = Password::reset($data, function (User $user, string $password) {
                $user->password = $password;
                $user->{$user->getRememberTokenName()} = Str::random(60);
                $user->save();
            });

            return $status === Password::PASSWORD_RESET
                ? ['error' => false, 'code' => 200, 'message' => '¡Contraseña cambiada!', 'data' => []]
                : ['error' => true, 'code' => 400, 'message' => 'Token inválido o expirado.', 'data' => []];
        } catch (Throwable $e) {
            report($e);
            return ['error' => true, 'code' => 400, 'message' => 'Error procesando solicitud.', 'data' => []];
        }
    }
}