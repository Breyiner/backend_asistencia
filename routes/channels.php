<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Definición de canales de broadcasting (WebSockets).
 *
 * Este archivo define qué usuarios pueden suscribirse a qué canales.
 * Se carga desde BroadcastServiceProvider.
 *
 * Tipos de canales en Laravel:
 * - Channel: canal público (sin autenticación)
 * - PrivateChannel: canal privado (requiere autenticación)
 * - PresenceChannel: canal de presencia (sabe quién está conectado)
 *
 * El callback de autorización retorna:
 * - true/datos del usuario: acceso permitido
 * - false: acceso denegado (403)
 */

/**
 * Canal privado por usuario: users.{id}
 *
 * Permite que cada usuario solo se suscriba a su propio canal.
 * Usado para enviar notificaciones en tiempo real.
 *
 * Frontend:
 * echo.private(`users.${userId}`)
 *     .listen('.notification.created', callback)
 *
 * Autorización:
 * - Solo el usuario autenticado puede suscribirse a su propio canal
 * - Comparación estricta de IDs casteados a int para evitar problemas de tipo
 *
 * @param mixed $user Usuario autenticado (inyectado por Sanctum)
 * @param int   $id   ID del canal solicitado (del parámetro de ruta)
 * @return bool true si el usuario puede suscribirse, false si no
 */
Broadcast::channel('users.{id}', function ($user, $id) {
    // Solo permite si el ID del usuario autenticado coincide con el ID del canal
    return (int) $user->id === (int) $id;
});