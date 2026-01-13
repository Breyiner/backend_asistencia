<?php

namespace App\Services\Auth;

use App\Enums\TokenAbility;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{

    public function register($dataUser)
    {

        try {

            DB::beginTransaction();

            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'telephone_number' => $telephoneNumber,
                'email' => $email,
                'password' => $password,
                'document_number' => $documentNumber,
                'document_type_id' => $documentTypeId,
            ] = $dataUser;

            $user = User::create([
                'email' => $email,
                'password' => Hash::make($password),
                'document_number' => $documentNumber,
                'document_type_id' => $documentTypeId,
            ]);

            $profile = UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'telephone_number' => $telephoneNumber
            ]);

            $user->assignRole('Pendiente');

            DB::commit();

            event(new Registered($user));

            return [
                'error' => false,
                'code' => 201,
                'data' => [
                    'user' => $user,
                    'password' => $password,
                    'profile' => $profile
                ],
                'message' => 'Usuario registrado con éxito',
            ];

        } catch (Exception $e) {

            DB::rollBack();
            return [
                "error" => true,
                "code" => 500,
                "message" => "Ocurrió un error al registrar el usuario  {$e->getMessage()}",
            ];
        }
    }

    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Credenciales incorrectas.",
            ];
        }

        if (!$user->hasVerifiedEmail()) {
            return [
                "error" => true,
                "code" => 403,
                "message" => "El correo no ha sido verificado",
                "errorKey" => "email_not_verified",
            ];
        }

        switch ($user->status_id) {
            case 2:
                return [
                    "error" => true,
                    "code" => 403,
                    "message" => "El usuario está inactivo",
                    "errorKey" => "user_inactive",
                ];
        }

        if (!Auth::attempt($credentials)) {
            return [
                "error" => true,
                "code" => 401,
                "message" => "Credenciales incorrectas.",
            ];
        }

        $accessToken = $this->generateAccessToken($user);

        $refreshToken = $this->generateRefreshToken($user);

        $cookieToken = cookie(
            'access_token',
            $accessToken,
            60 * 24 * 365 * 100,
            '/',
            null,
            false,
            false,
            false,
            'lax'
        );

        $cookieRefreshToken = cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 365 * 100,
            '/',
            null,
            false,
            false,
            false,
            'lax'
        );

        return [
            "error" => false,
            "code" => 200,
            "message" => "Logueo exitoso",
            "data" => [
                'id' => $user->id,
                'cookieToken' => $cookieToken,
                'cookieRefreshToken' => $cookieRefreshToken,
                'token' => $accessToken,
                'refreshToken' => $refreshToken
            ]
        ];
    }

    private function generateAccessToken($user)
    {

        return $user->createToken(
            'accessToken',
            [TokenAbility::ACCESS_API->value],
            Carbon::now()->addMinutes(config('sanctum.access_token_expiration'))
        )->plainTextToken;
    }

    private function generateRefreshToken($user)
    {

        return $user->createToken(
            'refreshToken',
            [TokenAbility::ISSUE_ACCESS_TOKEN->value],
            Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration'))
        )->plainTextToken;
    }

    public function refreshToken(string $currentRefreshToken, User $user)
    {

        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        $accessToken = $this->generateAccessToken($user);

        $refreshToken = $this->renewRefreshToken($refreshToken, $user) ?: $currentRefreshToken;

        $cookieToken = cookie(
            'access_token',
            $accessToken,
            60 * 24 * 365 * 100,
            '/',
            null,
            false,
            false,
            false,
            'lax'
        );

        $cookieRefreshToken = cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 365 * 100,
            '/',
            null,
            false,
            false,
            false,
            'lax'
        );

        return [
            "error" => false,
            "code" => 200,
            "message" => "Token renovado con éxito",
            "data" => [
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
                'cookieToken' => $cookieToken,
                'cookieRefreshToken' => $cookieRefreshToken,
            ]
        ];
    }

    private function renewRefreshToken(PersonalAccessToken $refreshToken, User $user)
    {

        $expiresToken = Carbon::parse($refreshToken->expires_at);

        $remainingTime = $expiresToken->diffInSeconds(Carbon::now(), false);

        if ($remainingTime < 60 * 60 * 24) {

            $refreshToken->delete();

            return $user->createToken(
                'refreshToken',
                [TokenAbility::ISSUE_ACCESS_TOKEN->value],
                Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration'))
            )->plainTextToken;
        }

        return null;
    }

    public function createExpiredCookies()
    {
        $expiredAccessToken = cookie(
            'access_token',
            '',
            -1,
            '/',
            null,
            false,
            false,
            false,
            'lax'
        );

        $expiredRefreshToken = cookie(
            'refresh_token',
            '',
            -1,
            '/',
            null,
            false,
            false,
            false,
            'lax'
        );

        return [
            'expiredAccessToken' => $expiredAccessToken,
            'expiredRefreshToken' => $expiredRefreshToken,
        ];
    }


    public function logOut(User $user)
    {
        $user->tokens()->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Cesión Cerrada con éxito",
            "data" => []
        ];
    }
}
