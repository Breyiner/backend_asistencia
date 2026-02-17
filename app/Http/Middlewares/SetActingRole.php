<?php

namespace App\Http\Middlewares;

use App\Helpers\ResponseFormatter;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class SetActingRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $roleId = $request->header('X-Acting-Role-Id');

        if (!$roleId) {
            return ResponseFormatter::error(
                'Debes enviar X-Acting-Role-Id.',
                422,
                [],
                'acting_role_required'
            );
        }

        if (!ctype_digit((string) $roleId)) {
            return ResponseFormatter::error(
                'X-Acting-Role-Id inválido.',
                422,
                [],
                'acting_role_invalid'
            );
        }

        $roleId = (int) $roleId;

        if (!$user || !$user->hasRole($roleId)) {
            return ResponseFormatter::error(
                'Rol activo no asignado al usuario.',
                403,
                [],
                'acting_role_not_assigned'
            );
        }

        $role = Role::query()
            ->select(['id', 'code', 'guard_name'])
            ->with(['permissions:id,name,guard_name'])
            ->find($roleId);

        if (!$role) {
            return ResponseFormatter::error(
                'Rol activo no existe.',
                404,
                [],
                'acting_role_not_found'
            );
        }

        $request->attributes->set('acting_role_id', $role->id);
        $request->attributes->set('acting_role_code', $role->code);
        $request->attributes->set('acting_role_permissions', $role->permissions->pluck('name')->all());

        return $next($request);
    }
}