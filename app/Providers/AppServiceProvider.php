<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios principal de la aplicación.
 *
 * Responsable de configurar servicios globales que aplican
 * a toda la aplicación, como rate limiting de rutas.
 *
 * Se ejecuta en cada request antes de que las rutas sean procesadas.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de dependencias.
     *
     * Aquí se registran bindings, singletons y servicios personalizados.
     * Se ejecuta antes de boot().
     */
    public function register(): void
    {
        // Sin registros adicionales en este provider
    }

    /**
     * Inicializa servicios de la aplicación.
     *
     * Configura tres limitadores de velocidad (rate limiters):
     *
     * 1. 'api': Para endpoints generales de la API
     *    - 120 requests por minuto
     *    - Limitado por ID de usuario autenticado o IP si es invitado
     *    - Aplicado con middleware: throttle:api
     *
     * 2. 'auth': Para endpoints de autenticación (login, registro)
     *    - 10 requests por minuto
     *    - Limitado solo por IP (sin usuario autenticado)
     *    - Previene ataques de fuerza bruta
     *    - Aplicado con middleware: throttle:auth
     *
     * 3. 'verification': Para reenvío de emails de verificación
     *    - 6 requests por minuto
     *    - Limitado por IP
     *    - Previene spam de emails de verificación
     *    - Aplicado con middleware: throttle:verification
     */
    public function boot(): void
    {
        /**
         * Rate limiter para la API general.
         *
         * Usa el ID del usuario si está autenticado, o la IP si es invitado.
         * Esto permite 120 requests/min por usuario, no por IP compartida.
         * El operador ?: usa la IP si user() es null (usuario no autenticado).
         */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip());
        });

        /**
         * Rate limiter para endpoints de autenticación.
         *
         * Más restrictivo (10 req/min) para prevenir:
         * - Ataques de fuerza bruta en login
         * - Registro masivo automatizado
         * - Enumeración de usuarios
         *
         * Siempre por IP (no hay usuario autenticado en auth endpoints).
         */
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        /**
         * Rate limiter para reenvío de verificación de email.
         *
         * Limita el reenvío de emails de verificación para:
         * - Prevenir spam hacia direcciones de email
         * - Evitar abuso del servicio de correo
         * - 6 intentos por minuto por IP
         */
        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });
    }
}