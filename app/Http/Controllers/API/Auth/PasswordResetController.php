<?php

namespace App\Http\Controllers\API\Auth;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordReset\ForgotPasswordRequest;
use App\Http\Requests\PasswordReset\ResetPasswordRequest;
use App\Services\PasswordReset\PasswordResetService;
use Illuminate\Http\Request;

/**
 * Controlador para recuperación y restablecimiento de contraseñas.
 */
class PasswordResetController extends Controller
{
    /**
     * Servicio que contiene la lógica de recuperación de contraseñas.
     *
     * @param PasswordResetService $service
     */
    public function __construct(protected PasswordResetService $service) {}

    /**
     * Solicita la recuperación de contraseña enviando email con token.
     *
     * POST /api/auth/forgot-password
     *
     * Recibe el email, delega la generación de token y envío del correo al servicio
     * y siempre responde de forma genérica por razones de seguridad.
     *
     * @param  ForgotPasswordRequest  $request  Email validado.
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgot(ForgotPasswordRequest $request)
    {
        // Ejecuta el flujo de "olvidé mi contraseña" en el servicio.
        $response = $this->service->forgot($request->validated());

        // Devuelve error o éxito según la respuesta del servicio.
        return $response['error']
            ? ResponseFormatter::error($response['message'], $response['code'])
            : ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Restablece la contraseña usando el token de recuperación.
     *
     * POST /api/auth/reset-password
     *
     * Valida email, token y nueva contraseña, y delega al servicio la verificación
     * del token y la actualización segura del password.
     *
     * @param  ResetPasswordRequest  $request  Email, token y nueva contraseña validados.
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(ResetPasswordRequest $request)
    {
        // Ejecuta el flujo de reset (validar token, actualizar password, logout global).
        $response = $this->service->reset($request->validated());

        // Devuelve error o éxito según la respuesta del servicio.
        return $response['error']
            ? ResponseFormatter::error($response['message'], $response['code'])
            : ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
