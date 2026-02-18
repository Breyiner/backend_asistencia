<?php

namespace App\Http\Middlewares;

use App\Helpers\ResponseFormatter;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que establece el rol activo del usuario en el request.
 *
 * En el sistema, los usuarios pueden tener múltiples roles.
 * Este middleware permite que el usuario opere bajo un rol específico
 * enviando el header X-Acting-Role-Id.
 *
 * El rol activo determina:
 * - Qué permisos tiene el usuario en este request
 * - Qué datos puede ver (scope de consultas)
 * - Qué acciones puede realizar
 *
 * Datos inyectados en el request (accesibles desde controladores y policies):
 * - acting_role_id: ID del rol activo
 * - acting_role_code: Código del rol (ej: 'ADMIN', 'INSTRUCTOR')
 * - acting_role_permissions: Array de nombres de permisos del rol
 *
 * Validaciones:
 * 1. Header X-Acting-Role-Id debe estar presente
 * 2. Debe ser un número entero válido
 * 3. El usuario debe tener asignado ese rol
 * 4. El rol debe existir en la BD
 *
 * Registro como 'acting.role' en bootstrap/app.php:
 * $middleware->alias(['acting.role' => SetActingRole::class])
 *
 * Uso en frontend (apiClient.js):
 * headers['X-Acting-Role-Id'] = getCurrentRoleId();
 */
class SetActingRole
{
    /**
     * Procesa la petición y establece el rol activo.
     *
     * @param Request $request Petición HTTP entrante
     * @param Closure $next    Siguiente middleware en la cadena
     * @return Response Respuesta (error o continúa)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user   = $request->user();
        $roleId = $request->header('X-Acting-Role-Id');

        /**
         * VALIDACIÓN 1: Header requerido.
         *
         * El frontend siempre debe enviar X-Acting-Role-Id.
         */
        if (!$roleId) {
            return ResponseFormatter::error(
                'Debes enviar X-Acting-Role-Id.',
                422,
                [],
                'acting_role_required'
            );
        }

        /**
         * VALIDACIÓN 2: Debe ser un número entero.
         *
         * ctype_digit verifica que sea solo dígitos (sin negativos ni decimales).
         * Castea a string primero para asegurar compatibilidad.
         */
        if (!ctype_digit((string) $roleId)) {
            return ResponseFormatter::error(
                'X-Acting-Role-Id inválido.',
                422,
                [],
                'acting_role_invalid'
            );
        }

        // Convierte a entero para comparaciones
        $roleId = (int) $roleId;

        /**
         * VALIDACIÓN 3: El usuario debe tener el rol asignado.
         *
         * hasRole() de Spatie verifica que el usuario tenga el rol.
         * Previene que un usuario use un rol que no le pertenece.
         */
        if (!$user || !$user->hasRole($roleId)) {
            return ResponseFormatter::error(
                'Rol activo no asignado al usuario.',
                403,
                [],
                'acting_role_not_assigned'
            );
        }

        /**
         * VALIDACIÓN 4: El rol debe existir en la BD.
         *
         * Carga el rol con sus permisos para inyectarlos en el request.
         * Solo carga campos necesarios para optimizar la consulta.
         */
        $role = Role::query()
            ->select(['id', 'code', 'guard_name'])
            ->with(['permissions:id,name,guard_name']) // Eager load de permisos
            ->find($roleId);

        if (!$role) {
            return ResponseFormatter::error(
                'Rol activo no existe.',
                404,
                [],
                'acting_role_not_found'
            );
        }

        /**
         * Inyecta datos del rol activo en los atributos del request.
         *
         * Los atributos son accesibles desde controladores y policies:
         * $request->attributes->get('acting_role_code')
         * $request->attributes->get('acting_role_permissions')
         */

        // ID numérico del rol activo
        $request->attributes->set('acting_role_id', $role->id);

        // Código del rol (ej: 'ADMIN', 'INSTRUCTOR', 'GESTOR_FICHAS')
        $request->attributes->set('acting_role_code', $role->code);

        // Array de nombres de permisos del rol
        // (ej: ['users.view', 'users.create', 'apprentices.view'])
        $request->attributes->set(
            'acting_role_permissions',
            $role->permissions->pluck('name')->all()
        );

        // Continúa con el siguiente middleware/controlador
        return $next($request);
    }
}