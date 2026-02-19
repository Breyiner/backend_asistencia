<?php

namespace App\Enums;

/**
 * Enum de capacidades (abilities) para tokens de Sanctum.
 *
 * Define los tipos de acciones que puede realizar un token.
 * Usado con Laravel Sanctum para control granular de permisos
 * de tokens de API.
 *
 * Uso típico en el sistema de autenticación:
 * - ACCESS_API: token de corta duración para acceder a la API
 * - ISSUE_ACCESS_TOKEN: token de larga duración (refresh) para emitir nuevos access tokens
 *
 * Uso en controladores:
 * $request->user()->tokenCan(TokenAbility::ACCESS_API->value);
 *
 * Uso al crear tokens:
 * $user->createToken('access', [TokenAbility::ACCESS_API->value]);
 * $user->createToken('refresh', [TokenAbility::ISSUE_ACCESS_TOKEN->value]);
 *
 * Uso en middleware:
 * Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value])
 *     ->group(function () { ... });
 */
enum TokenAbility: string
{
    /**
     * Capacidad para acceder a endpoints de la API.
     *
     * Este ability se asigna al access token (corta duración).
     * La mayoría de endpoints de la API lo requieren.
     *
     * Valor: 'access_api'
     */
    case ACCESS_API = 'access_api';

    /**
     * Capacidad para emitir nuevos access tokens.
     *
     * Este ability se asigna al refresh token (larga duración).
     * Solo el endpoint de refresh token lo requiere.
     * Esto previene que el refresh token se use para acceder a la API directamente.
     *
     * Valor: 'issue_access_token'
     */
    case ISSUE_ACCESS_TOKEN = 'issue_access_token';
}