<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para broadcasting (WebSockets).
 *
 * Configura las rutas de autenticación de canales privados
 * y carga las definiciones de canales de la aplicación.
 *
 * Laravel necesita un endpoint HTTP para autenticar canales privados:
 * Cuando el frontend intenta suscribirse a un canal privado,
 * hace una petición POST a /api/broadcasting/auth con el token.
 * Laravel verifica el token y autoriza (o deniega) el canal.
 *
 * Flujo de autenticación de canal privado:
 * 1. Frontend: echo.private('users.1')
 * 2. Echo hace POST a /api/broadcasting/auth
 * 3. Este provider verifica via Sanctum
 * 4. Si válido, autoriza el canal
 * 5. Frontend recibe eventos del canal
 */
class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Registra las rutas y canales de broadcasting.
     *
     * Configura:
     * 1. La ruta de autenticación de canales con middleware Sanctum
     * 2. Las definiciones de canales desde routes/channels.php
     */
    public function boot(): void
    {
        /**
         * Registra la ruta de autenticación de canales.
         *
         * Opciones:
         * - middleware: 'auth:sanctum' valida el Bearer token del frontend
         * - prefix: 'api' → la ruta queda en /api/broadcasting/auth
         *
         * Esta URL debe coincidir con authEndpoint en echo.js:
         * authEndpoint: "http://localhost:8000/api/broadcasting/auth"
         */
        Broadcast::routes([
            'middleware' => ['auth:sanctum'],
            'prefix'     => 'api',
        ]);

        /**
         * Carga las definiciones de canales.
         *
         * channels.php define qué usuarios pueden acceder a qué canales:
         *
         * Broadcast::channel('users.{id}', function (User $user, int $id) {
         *     return (int) $user->id === $id; // Solo el propio usuario
         * });
         */
        require base_path('routes/channels.php');
    }
}