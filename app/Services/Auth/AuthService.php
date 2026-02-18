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

/**
 * Servicio de lógica de negocio para autenticación y gestión de sesiones.
 *
 * Implementa el flujo completo de autenticación con Laravel Sanctum:
 * registro, login, renovación de tokens y cierre de sesión.
 * Maneja dos tipos de token: access token (corta duración) y refresh token (larga duración),
 * ambos enviados también como cookies para mayor seguridad en el navegador.
 */
class AuthService
{
    /**
     * Registra un nuevo usuario en el sistema.
     *
     * Ejecuta la creación dentro de una transacción porque involucra
     * dos tablas (users y user_profiles). Si alguna falla, se revierte todo.
     * El email de verificación se envía DESPUÉS del commit para asegurar
     * que el usuario ya exista en BD cuando el link sea utilizado.
     *
     * @param  mixed  $dataUser  Datos del usuario validados desde el request.
     * @return array
     */
    public function register($dataUser)
    {
        try {
            // Inicia la transacción: usuario y perfil deben crearse juntos o no crearse.
            DB::beginTransaction();

            // Desestructura los datos del request en variables locales para mayor claridad.
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'telephone_number' => $telephoneNumber,
                'email' => $email,
                'password' => $password,
                'document_number' => $documentNumber,
                'document_type_id' => $documentTypeId,
            ] = $dataUser;

            // Operación 1: Crea el usuario con la contraseña hasheada.
            $user = User::create([
                'email' => $email,
                'password' => Hash::make($password), // Nunca se guarda la contraseña en texto plano.
                'document_number' => $documentNumber,
                'document_type_id' => $documentTypeId,
            ]);

            // Operación 2: Crea el perfil vinculado al usuario recién creado.
            // Si falla, el rollback deshace también la creación del usuario.
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'telephone_number' => $telephoneNumber
            ]);

            // Operación 3: Asigna el rol 'Pendiente' hasta que verifique su email.
            $user->assignRole('Pendiente');

            // Confirma las tres operaciones de forma atómica.
            DB::commit();

            // Se envía el email DESPUÉS del commit para garantizar que el usuario
            // ya esté persistido cuando haga clic en el enlace de verificación.
            $user->sendEmailVerificationNotification();

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
            // Revierte usuario, perfil y asignación de rol si cualquier operación falló.
            DB::rollBack();
            return [
                "error" => true,
                "code" => 500,
                "message" => "Ocurrió un error al registrar el usuario  {$e->getMessage()}",
            ];
        }
    }

    /**
     * Valida credenciales e inicia sesión del usuario.
     *
     * El flujo de validación es secuencial y falla rápido:
     * 1. Verifica que el usuario exista.
     * 2. Verifica que haya confirmado su email.
     * 3. Verifica que su cuenta esté activa.
     * 4. Verifica que la contraseña sea correcta.
     *
     * Si todo es válido, genera access token, refresh token y sus respectivas cookies.
     *
     * @param  array  $credentials  Email y password validados desde el request.
     * @return array
     */
    public function login(array $credentials)
    {
        // Busca el usuario por email antes de intentar autenticar, para poder
        // dar mensajes de error más específicos que un genérico "credenciales incorrectas".
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Credenciales incorrectas.",
            ];
        }

        // Un usuario sin email verificado no puede iniciar sesión.
        // errorKey permite que el frontend identifique este caso y muestre el botón de reenvío.
        if (!$user->hasVerifiedEmail()) {
            return [
                "error" => true,
                "code" => 403,
                "message" => "El correo no ha sido verificado",
                "errorKey" => "email_not_verified",
            ];
        }

        // Verifica el estado de la cuenta; status_id = 2 significa inactivo.
        switch ($user->status_id) {
            case 2:
                return [
                    "error" => true,
                    "code" => 403,
                    "message" => "El usuario está inactivo",
                    "errorKey" => "user_inactive",
                ];
        }

        // Auth::attempt valida la contraseña y autentica al usuario en la sesión actual.
        // Se hace al final para no ejecutarlo si ya falló alguna validación previa.
        if (!Auth::attempt($credentials)) {
            return [
                "error" => true,
                "code" => 401,
                "message" => "Credenciales incorrectas.",
            ];
        }

        // Genera los dos tokens con distintas capacidades y tiempos de expiración.
        $accessToken  = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        // Crea las cookies para ambos tokens. Se envían al navegador vía Set-Cookie.
        // minutos = 60*24*365*100 ≈ 100 años (prácticamente permanente; la expiración real
        // la controla el token en BD, no la cookie en sí).
        $cookieToken = cookie(
            'access_token',
            $accessToken,
            60 * 24 * 365 * 100,
            '/',
            null,
            false, // secure: false en desarrollo; debería ser true en producción con HTTPS.
            false, // httpOnly: false para permitir acceso desde JS si es necesario.
            false,
            'lax'  // sameSite: protege contra CSRF en requests cross-site.
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
                'user' => $user->auth_data, // Accessor que retorna solo los datos públicos del usuario.
                'cookieToken' => $cookieToken,
                'cookieRefreshToken' => $cookieRefreshToken,
                'token' => $accessToken,
                'refreshToken' => $refreshToken
            ]
        ];
    }

    /**
     * Genera un access token de corta duración para el usuario.
     *
     * La capacidad ACCESS_API limita este token a llamadas normales a la API.
     * El tiempo de expiración se toma de la configuración de Sanctum.
     *
     * @param  User  $user
     * @return string  Token en texto plano (solo disponible en este momento, no se puede recuperar después).
     */
    private function generateAccessToken($user)
    {
        return $user->createToken(
            'accessToken',
            [TokenAbility::ACCESS_API->value],
            Carbon::now()->addMinutes(config('sanctum.access_token_expiration'))
        )->plainTextToken;
    }

    /**
     * Genera un refresh token de larga duración para el usuario.
     *
     * La capacidad ISSUE_ACCESS_TOKEN limita este token exclusivamente
     * al endpoint de renovación, impidiendo que se use para llamadas normales.
     *
     * @param  User  $user
     * @return string  Token en texto plano.
     */
    private function generateRefreshToken($user)
    {
        return $user->createToken(
            'refreshToken',
            [TokenAbility::ISSUE_ACCESS_TOKEN->value],
            Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration'))
        )->plainTextToken;
    }

    /**
     * Renueva el access token y condicionalmente el refresh token.
     *
     * El refresh token solo se renueva si le queda menos de 24 horas de vida,
     * evitando crear tokens innecesariamente en cada request.
     *
     * @param  string  $currentRefreshToken  Refresh token actual en texto plano.
     * @param  User    $user                 Usuario autenticado por Sanctum.
     * @return array
     */
    public function refreshToken(string $currentRefreshToken, User $user)
    {
        // Busca el modelo del refresh token en BD a partir del texto plano.
        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        // Siempre genera un nuevo access token.
        $accessToken = $this->generateAccessToken($user);

        // Solo renueva el refresh token si está próximo a vencer; si no, reutiliza el actual.
        $refreshToken = $this->renewRefreshToken($refreshToken, $user) ?: $currentRefreshToken;

        // Actualiza las cookies con los tokens nuevos (o el mismo refresh si no fue renovado).
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

    /**
     * Renueva el refresh token solo si le queda menos de 24 horas de vida.
     *
     * Elimina el token anterior y genera uno nuevo con tiempo de expiración completo.
     * Retorna null si el token aún tiene vida suficiente y no necesita renovarse.
     *
     * @param  PersonalAccessToken  $refreshToken  Modelo del refresh token actual.
     * @param  User                 $user          Usuario dueño del token.
     * @return string|null  Nuevo token en texto plano, o null si no fue renovado.
     */
    private function renewRefreshToken(PersonalAccessToken $refreshToken, User $user)
    {
        $expiresToken = Carbon::parse($refreshToken->expires_at);

        // diffInSeconds con false como segundo argumento retorna negativo si expires_at ya pasó,
        // y positivo si aún le queda tiempo. Aquí se usa para medir el tiempo restante.
        $remainingTime = $expiresToken->diffInSeconds(Carbon::now(), false);

        // Si le quedan menos de 24 horas (86400 seg), se renueva para evitar que expire pronto.
        if ($remainingTime < 60 * 60 * 24) {
            // Elimina el token viejo antes de crear el nuevo para evitar tokens huérfanos en BD.
            $refreshToken->delete();

            return $user->createToken(
                'refreshToken',
                [TokenAbility::ISSUE_ACCESS_TOKEN->value],
                Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration'))
            )->plainTextToken;
        }

        // El token aún tiene vida suficiente; no se renueva.
        return null;
    }

    /**
     * Crea cookies expiradas para limpiar los tokens del navegador al cerrar sesión.
     *
     * Al enviar cookies con tiempo negativo (-1), el navegador las elimina inmediatamente.
     * Esto asegura que aunque el servidor revoque los tokens, el cliente también los limpie.
     *
     * @return array  Cookies expiradas para access_token y refresh_token.
     */
    public function createExpiredCookies()
    {
        // minutos = -1 hace que el navegador elimine la cookie al recibirla.
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

    /**
     * Cierra la sesión del usuario revocando todos sus tokens activos.
     *
     * Elimina todos los tokens del usuario en BD (access y refresh).
     * El controlador complementa esto enviando las cookies expiradas al cliente.
     *
     * @param  User  $user  Usuario autenticado cuya sesión se va a cerrar.
     * @return array
     */
    public function logOut(User $user)
    {
        // Revoca todos los tokens activos del usuario, no solo el actual.
        // Esto cierra sesión en todos los dispositivos donde esté logueado.
        $user->tokens()->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Cesión Cerrada con éxito",
            "data" => []
        ];
    }
}