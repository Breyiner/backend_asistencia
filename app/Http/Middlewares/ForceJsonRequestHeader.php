<?php

namespace App\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que fuerza el header Accept: application/json.
 *
 * Garantiza que todas las peticiones a la API sean tratadas como
 * peticiones JSON, independientemente del header que envíe el cliente.
 *
 * Problema que resuelve:
 * Sin este middleware, Laravel puede retornar HTML en lugar de JSON
 * cuando ocurre un error (ej: 404, 422, 500) si el cliente no envía
 * Accept: application/json.
 *
 * Esto es especialmente importante para:
 * - Errores de validación (422)
 * - Errores de autenticación (401)
 * - Rutas no encontradas (404)
 * - Excepciones no controladas (500)
 *
 * Registro en bootstrap/app.php:
 * ->withMiddleware(function (Middleware $middleware) {
 *     $middleware->prepend(ForceJsonRequestHeader::class);
 * })
 */
class ForceJsonRequestHeader
{
    /**
     * Procesa la petición entrante.
     *
     * Establece el header Accept a 'application/json' antes de
     * que la petición sea procesada por el resto de la aplicación.
     *
     * @param Request $request Petición HTTP entrante
     * @param Closure $next    Siguiente middleware en la cadena
     * @return Response Respuesta de la aplicación
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Fuerza el header Accept a application/json
        // Esto garantiza que Laravel siempre retorne JSON en lugar de HTML
        $request->headers->set('Accept', 'application/json');

        // Continúa con el siguiente middleware/controlador
        return $next($request);
    }
}