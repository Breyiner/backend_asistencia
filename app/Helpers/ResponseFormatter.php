<?php

namespace App\Helpers;

/**
 * Helper para formatear respuestas JSON de la API.
 *
 * Centraliza y estandariza la estructura de todas las respuestas
 * de la API, garantizando consistencia en el frontend.
 *
 * Estructura de respuesta exitosa:
 * {
 *   "success": true,
 *   "code": 200,
 *   "message": "Operación exitosa",
 *   "data": {...},
 *   "paginate": {...},
 *   "summary": {...}
 * }
 *
 * Estructura de respuesta de error:
 * {
 *   "success": false,
 *   "code": 422,
 *   "message": "Datos inválidos",
 *   "errors": ["El campo es obligatorio"],
 *   "errorKey": "validation_error"
 * }
 *
 * Uso en controladores:
 * return ResponseFormatter::success('Usuario creado', 201, $user);
 * return ResponseFormatter::error('No encontrado', 404);
 *
 * Uso con paginación:
 * return ResponseFormatter::success('Usuarios obtenidos', 200, $users->items(), $users);
 */
class ResponseFormatter
{
    /**
     * Retorna una respuesta JSON de éxito.
     *
     * Usada para operaciones que se completaron correctamente:
     * - GET: retorno de recursos
     * - POST: creación exitosa
     * - PATCH/PUT: actualización exitosa
     * - DELETE: eliminación exitosa
     *
     * @param string $message  Mensaje descriptivo de la operación
     * @param int    $status   Código HTTP de éxito (200, 201, etc.)
     * @param mixed  $data     Datos principales de la respuesta (modelo, array, colección, etc.)
     * @param array  $paginate Metadata de paginación (current_page, last_page, total, etc.)
     * @param array  $summary  Datos adicionales de resumen (estadísticas, catálogos, etc.)
     * @return \Illuminate\Http\JsonResponse
     *
     * @example
     * // Respuesta simple
     * return ResponseFormatter::success('Usuario obtenido', 200, $user);
     *
     * @example
     * // Respuesta con paginación
     * $paginated = User::paginate(15);
     * return ResponseFormatter::success(
     *     'Usuarios obtenidos',
     *     200,
     *     $paginated->items(),
     *     $paginated
     * );
     *
     * @example
     * // Respuesta con summary (datos extra como catálogos)
     * return ResponseFormatter::success(
     *     'Roles obtenidos',
     *     200,
     *     $roles,
     *     [],
     *     ['permissions' => $allPermissions]
     * );
     */
    public static function success(
        $message  = "Operación exitosa",
        $status   = 200,
        $data,
        $paginate = [],
        $summary  = []
    ) {
        return response()->json([
            // Indicador de éxito para que el frontend identifique el resultado
            "success"  => true,

            // Código HTTP repetido en el body para acceso conveniente
            "code"     => $status,

            // Mensaje legible para mostrar al usuario o para debugging
            "message"  => $message,

            // Datos principales: modelo, colección, array, etc.
            "data"     => $data,

            // Metadata de paginación: se envía cuando los datos son paginados
            // Estructura típica: { current_page, last_page, per_page, total, ... }
            "paginate" => $paginate,

            // Datos adicionales: catálogos, estadísticas, totales, etc.
            // Útil para enviar datos complementarios junto a la respuesta principal
            "summary"  => $summary,
        ], $status);
    }

    /**
     * Retorna una respuesta JSON de error.
     *
     * Usada para indicar que la operación no pudo completarse:
     * - 400: Bad Request (petición malformada)
     * - 401: Unauthorized (no autenticado)
     * - 403: Forbidden (no autorizado)
     * - 404: Not Found (recurso no encontrado)
     * - 422: Unprocessable Entity (errores de validación)
     * - 429: Too Many Requests (rate limiting)
     * - 500: Internal Server Error (error del servidor)
     *
     * @param string      $message  Mensaje descriptivo del error
     * @param int         $status   Código HTTP del error
     * @param array       $errors   Array de mensajes de error específicos (validaciones)
     * @param string|null $errorKey Clave del error para manejo programático en el frontend
     * @return \Illuminate\Http\JsonResponse
     *
     * @example
     * // Error simple
     * return ResponseFormatter::error('No encontrado', 404);
     *
     * @example
     * // Error de validación con detalles
     * return ResponseFormatter::error(
     *     'Datos inválidos',
     *     422,
     *     ['El email es obligatorio', 'El nombre es muy corto']
     * );
     *
     * @example
     * // Error con errorKey para manejo en el frontend
     * return ResponseFormatter::error(
     *     'No autenticado',
     *     401,
     *     [],
     *     'not_authenticated'  // El frontend detecta esto y hace refresh del token
     * );
     */
    public static function error($message, $status, $errors = [], $errorKey = null)
    {
        return response()->json([
            // Indicador de fallo para que el frontend identifique el resultado
            "success"  => false,

            // Código HTTP repetido en el body para acceso conveniente
            "code"     => $status,

            // Mensaje legible del error (puede mostrarse al usuario)
            "message"  => $message,

            // Array de errores específicos (principalmente de validación)
            // Ejemplo: ["El email es obligatorio", "La contraseña es muy corta"]
            "errors"   => $errors,

            // Clave de error para manejo programático en el frontend
            // Permite identificar tipos de error sin depender del mensaje
            // Ejemplos: 'not_authenticated', 'not_authorized', 'throttle_requests'
            "errorKey" => $errorKey,
        ], $status);
    }
}