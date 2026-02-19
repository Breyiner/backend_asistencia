<?php

namespace App\Exceptions;

use App\Helpers\ResponseFormatter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Manejador centralizado de excepciones para la API.
 *
 * Intercepta todas las excepciones de la aplicación y las transforma
 * en respuestas JSON consistentes usando ResponseFormatter.
 *
 * Ventajas de este enfoque centralizado:
 * - Respuestas uniformes en toda la API
 * - Un solo lugar para modificar el manejo de errores
 * - Separación de responsabilidades (el controlador no maneja errores)
 * - Fácil de testear
 *
 * Integración con Laravel (en bootstrap/app.php o Handler.php):
 * $exceptions->render(function (Throwable $e, Request $request) {
 *     if ($request->expectsJson()) {
 *         return ApiExceptionHandler::handle($e);
 *     }
 * });
 *
 * Jerarquía de manejo (orden de evaluación):
 * 1. AuthenticationException / UnauthorizedHttpException → 401
 * 2. AuthorizationException → 403
 * 3. ModelNotFoundException / NotFoundHttpException → 404
 * 4. ValidationException → 422
 * 5. HttpException (genérico) → código del error
 * 6. ThrottleRequestsException → 429
 * 7. Cualquier otro error → 500
 */
class ApiExceptionHandler
{
    /**
     * Maneja una excepción y retorna una respuesta JSON formateada.
     *
     * Evalúa el tipo de excepción en orden de especificidad
     * y retorna la respuesta apropiada para cada caso.
     *
     * @param Throwable $e Excepción capturada (cualquier tipo)
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada
     */
    public static function handle(Throwable $e)
    {
        /**
         * CASO 1: No autenticado (401)
         *
         * AuthenticationException: lanzada por middleware 'auth'
         * cuando no hay sesión/token válido.
         *
         * UnauthorizedHttpException: lanzada por Sanctum/Passport
         * cuando el token es inválido o expiró.
         *
         * errorKey 'not_authenticated' permite al frontend
         * identificar este error y ejecutar refresh token.
         */
        if ($e instanceof AuthenticationException || $e instanceof UnauthorizedHttpException) {
            return ResponseFormatter::error('No autenticado', 401, [], 'not_authenticated');
        }

        /**
         * CASO 2: No autorizado (403)
         *
         * AuthorizationException: lanzada por Gates y Policies
         * cuando el usuario autenticado no tiene permisos
         * para realizar la acción solicitada.
         *
         * Diferencia con 401:
         * - 401: No identificado (no está autenticado)
         * - 403: Identificado pero sin permiso (autenticado pero no autorizado)
         */
        if ($e instanceof AuthorizationException) {
            return ResponseFormatter::error('No autorizado', 403, [], 'not_authorized');
        }

        /**
         * CASO 3: Recurso no encontrado (404)
         *
         * ModelNotFoundException: lanzada por Eloquent cuando
         * se usa findOrFail() y el registro no existe en la BD.
         *
         * NotFoundHttpException: lanzada por el router de Laravel
         * cuando la ruta solicitada no existe.
         *
         * Ambos se manejan igual: recurso no encontrado.
         */
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return ResponseFormatter::error('Recurso no encontrado', 404);
        }

        /**
         * CASO 4: Datos de validación inválidos (422)
         *
         * ValidationException: lanzada por $request->validate()
         * o Validator::make() cuando los datos no cumplen las reglas.
         *
         * Proceso de transformación de errores:
         * - $e->errors() retorna: ['field' => ['error1', 'error2'], ...]
         * - collect()->flatten(): aplana a ['error1', 'error2', 'error3', ...]
         * - values()->all(): reindexación y conversión a array PHP
         *
         * Ejemplo de $e->errors():
         * {
         *   "email": ["El email es obligatorio", "El formato es inválido"],
         *   "name": ["El nombre es obligatorio"]
         * }
         *
         * Resultado aplanado:
         * ["El email es obligatorio", "El formato es inválido", "El nombre es obligatorio"]
         */
        if ($e instanceof ValidationException) {
            // Aplana los errores de validación de objeto anidado a array simple
            $flattenedErrors = collect($e->errors())
                ->flatten()  // Aplana array de arrays a array simple
                ->values()   // Reindexación numérica del array
                ->all();     // Convierte Collection a array PHP

            return ResponseFormatter::error('Datos inválidos', 422, $flattenedErrors);
        }

        /**
         * CASO 5: Error HTTP genérico
         *
         * HttpException: clase base para errores HTTP de Symfony.
         * Puede ser lanzada explícitamente con abort():
         *   abort(403, 'Acceso denegado');
         *   abort(405, 'Método no permitido');
         *
         * Usa el mensaje y código del propio error.
         * Fallback 'Error HTTP' si no hay mensaje.
         *
         * Nota: Este caso debe ir DESPUÉS de NotFoundHttpException
         * porque NotFoundHttpException extiende HttpException.
         */
        if ($e instanceof HttpException) {
            return ResponseFormatter::error(
                $e->getMessage() ?: 'Error HTTP', // Mensaje o fallback
                $e->getStatusCode()               // Código HTTP del error (403, 405, etc.)
            );
        }

        /**
         * CASO 6: Rate limiting (429)
         *
         * ThrottleRequestsException: lanzada por el middleware
         * 'throttle' cuando el usuario excede el límite de peticiones.
         *
         * Ejemplo de configuración del throttle:
         * Route::middleware('throttle:60,1')->group(...); // 60 req/minuto
         *
         * errorKey 'throttle_requests' permite al frontend
         * mostrar un mensaje específico o deshabilitar botones.
         *
         * Nota: ThrottleRequestsException extiende HttpException,
         * por lo que debe evaluarse ANTES del caso genérico HttpException
         * si se quiere un mensaje personalizado. En este handler está
         * después, pero funciona porque la evaluación sigue el orden
         * del código.
         */
        if ($e instanceof ThrottleRequestsException) {
            return ResponseFormatter::error(
                'Demasiadas solicitudes. Por favor, inténtalo de nuevo más tarde.',
                429,
                [],
                'throttle_requests'
            );
        }

        /**
         * CASO 7: Error interno del servidor (500)
         *
         * Captura cualquier excepción no manejada anteriormente.
         * Errores de programación, errores de BD, etc.
         *
         * Comportamiento según entorno:
         * - Debug activo (local/testing): incluye el mensaje real del error
         *   para facilitar depuración.
         * - Debug inactivo (producción): retorna array vacío para no
         *   exponer información sensible del servidor.
         *
         * config('app.debug') lee APP_DEBUG del .env
         */
        return ResponseFormatter::error(
            'Error interno del servidor',
            500,
            // En debug: muestra el mensaje del error para depuración
            // En producción: array vacío para no exponer detalles internos
            config('app.debug') ? [$e->getMessage()] : []
        );
    }
}