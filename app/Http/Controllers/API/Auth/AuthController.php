<?php

namespace App\Http\Controllers\API\Auth;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de autenticación y gestión de sesiones.
 *
 * Implementa el sistema de autenticación del aplicativo usando Laravel Sanctum,
 * access tokens y refresh tokens, más cookies HttpOnly para mayor seguridad.
 */
class AuthController extends Controller
{
    /**
     * Servicio de lógica de negocio para autenticación.
     *
     * @var AuthService
     */
    protected $authService;

    /**
     * Inyección de dependencias del servicio de autenticación.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        // Asigna el servicio para usarlo en todos los métodos del controlador.
        $this->authService = $authService;
    }

    /**
     * Registra un nuevo usuario en el sistema.
     *
     * POST /api/auth/register
     *
     * Usado para alta de usuarios (cuando el registro público está permitido).
     *
     * @param  RegisterRequest  $request  Datos de registro validados.
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        // Se pasa el request validado directamente al servicio.
        $data = $request;

        // El servicio crea el usuario, asigna rol por defecto y genera tokens.
        $response = $this->authService->register($data);

        // Si hay error (email ya existe, registro deshabilitado, etc.), se responde con error estándar.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Devuelve usuario creado y tokens de autenticación.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data']);
    }

    /**
     * Inicia sesión (login) de un usuario.
     *
     * POST /api/auth/login
     *
     * Valida credenciales y retorna usuario + tokens, además de setear cookies.
     *
     * @param  LoginRequest  $request  Credenciales validadas (email, password, etc.).
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        // Obtiene credenciales validadas.
        $credentials = $request->validated();

        // El servicio valida credenciales, genera tokens y cookies.
        $result = $this->authService->login($credentials);

        // Manejo de errores (credenciales incorrectas, usuario inactivo, etc.).
        if ($result['error'])
            return ResponseFormatter::error($result['message'], $result['code'], $result['errors'] ?? [], $result['errorKey'] ?? null);

        // Cookies HttpOnly para access y refresh token.
        $cookieToken = $result['data']['cookieToken'];
        $cookieRefresh = $result['data']['cookieRefreshToken'];

        // No se devuelven las cookies en el body, solo por header Set-Cookie.
        $bodyData = array_diff_key($result['data'], array_flip(['cookieToken', 'cookieRefreshToken']));

        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            $bodyData
        )->cookie($cookieToken)
         ->cookie($cookieRefresh);
    }

    /**
     * Renueva el access token usando un refresh token válido.
     *
     * POST /api/auth/refresh-token
     *
     * Requiere usuario autenticado (auth:sanctum) y refresh token vigente.
     *
     * @param  Request  $request  Request con refresh token en Bearer/cookie.
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        // Usuario autenticado por Sanctum.
        $user = Auth::user();

        // Refresh token enviado en Authorization: Bearer {token}.
        $currentRefreshToken = $request->bearerToken();

        // El servicio valida el refresh token, genera nuevos tokens y revoca los anteriores.
        $result = $this->authService->refreshToken($currentRefreshToken, $user);

        // Manejo de errores (token inválido/expirado/reutilizado).
        if ($result['error'])
            return ResponseFormatter::error($result['message'], $result['code'], $result['errors'] ?? [], $result['errorKey'] ?? null);

        // Cookies actualizadas para nuevos tokens.
        $cookieToken = $result['data']['cookieToken'];
        $cookieRefresh = $result['data']['cookieRefreshToken'];

        // Tokens nuevos que se devuelven en el body.
        $accessToken = $result['data']['accessToken'];
        $refreshToken = $result['data']['refreshToken'];

        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            [
                'token' => $accessToken,
                'refreshToken' => $refreshToken,
            ]
        )->cookie($cookieToken)
         ->cookie($cookieRefresh);
    }

    /**
     * Cierra la sesión del usuario autenticado.
     *
     * POST /api/auth/logout
     *
     * Revoca tokens en servidor y expira cookies en el cliente.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logOut(Request $request)
    {
        // Usuario autenticado actual.
        $user = Auth::user();

        // Crea cookies expiradas para limpiar las cookies de autenticación en el navegador.
        $expiredCookies = $this->authService->createExpiredCookies();

        // El servicio revoca el/los tokens del usuario.
        $result = $this->authService->logOut($user);

        // Respuesta de éxito con cookies expiradas adjuntas.
        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            $result['data']
        )->cookie($expiredCookies['expiredAccessToken'])
         ->cookie($expiredCookies['expiredRefreshToken']);
    }
}
