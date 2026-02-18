<?php

namespace App\Http\Controllers\API\EmailVerification;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResendVerification\ResendVerificationRequest;
use App\Services\EmailVerification\EmailVerificationService;
use Illuminate\Http\Request;

/**
 * Controlador para verificación de email de usuarios.
 *
 * Maneja el aviso de email no verificado, la verificación vía URL firmada
 * y el reenvío del correo de verificación.
 */
class EmailVerificationController extends Controller
{
    /**
     * Servicio de lógica de verificación de emails.
     *
     * @var EmailVerificationService
     */
    public function __construct(protected EmailVerificationService $service) {}

    /**
     * Respuesta estándar cuando el usuario debe verificar su email.
     *
     * GET /api/email/verification-notification
     *
     * Retorna 403 con una clave de error específica para que el frontend
     * muestre la pantalla de “verifica tu correo”.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notice()
    {
        // Respuesta de error para usuarios con email no verificado.
        return ResponseFormatter::error('Debes verificar tu correo.', 403, [], 'email_not_verified');
    }

    /**
     * Verifica el email del usuario usando la URL firmada.
     *
     * GET /api/email/verify/{id}/{hash}
     *
     * Valida ID y hash del usuario (además de la firma de la URL) y, si es válido,
     * marca el email como verificado y puede devolver info del usuario/token.
     *
     * @param  Request  $request  Request con parámetros de URL firmada.
     * @param  string   $id       ID del usuario.
     * @param  string   $hash     Hash del email del usuario.
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request, string $id, string $hash)
    {
        // Ejecuta la lógica de verificación en el servicio.
        $response = $this->service->verify($id, $hash);

        // Si hay error (link inválido, expirado, ya verificado, etc.), responde con error.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Verificación correcta: devuelve mensaje y datos (usuario/token si aplica).
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? null);
    }

    /**
     * Reenvía el email de verificación.
     *
     * POST /api/email/resend
     *
     * Recibe un email validado, delega al servicio el reenvío y siempre
     * devuelve un mensaje genérico (sin revelar si el email existe).
     *
     * @param  ResendVerificationRequest  $request  Email validado.
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(ResendVerificationRequest $request)
    {
        // Email u otros datos validados.
        $data = $request->validated();

        // Llama al servicio para gestionar el reenvío (incluye rate limiting, etc.).
        $response = $this->service->resend($data);

        // Si hay error (por ejemplo límite de intentos), responde en formato estándar.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa, normalmente con mensaje genérico.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? null);
    }
}
