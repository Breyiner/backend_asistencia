<?php

namespace App\Http\Controllers\API\Role;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\Role\RoleService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **Roles (Spatie)**.
 *
 * CRUD roles + sync permissions. selectable para asignación usuarios.
 * Protegidos: SUPER_ADMIN (no eliminar).
 *
 * **Endpoints clave**: index (paginado), selectable (selects), syncPermissions.
 *
 * @see RoleService Sync permissions/validaciones protegidos
 */
class RoleController extends Controller
{
    /**
     * Servicio de roles.
     */
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Lista roles paginada.
     * GET /api/roles
     *
     * Query: per_page. Incluye permissions_count, users_count.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->roleService->getAll($request->get('per_page', 10));

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? null,
            $response['summary'] ?? null
        );
    }

    /**
     * Roles para select (asignación usuarios).
     * GET /api/roles/selectable
     *
     * Sin paginación: [{id, name, description}]. Excluye SUPER_ADMIN.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectable(Request $request)
    {
        $response = $this->roleService->getAllForSelect();

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Detalle rol con permissions/usuarios.
     * GET /api/roles/{id}
     *
     * Incluye: permissions asignadas, users_count, permissions_count.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->roleService->getById($id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Crea rol nuevo.
     * POST /api/roles
     *
     * Valida: name único, description. No SUPER_ADMIN.
     *
     * @param StoreRoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRoleRequest $request)
    {
        $response = $this->roleService->create($request->validated());

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Actualiza rol.
     * PUT /api/roles/{id}
     *
     * Valida: name único (excepto actual). No SUPER_ADMIN.
     *
     * @param UpdateRoleRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoleRequest $request, string $id)
    {
        $response = $this->roleService->update($request->validated(), $id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Elimina rol (no SUPER_ADMIN, no usuarios asignados).
     * DELETE /api/roles/{id}
     *
     * Error 409 si users_count > 0.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->roleService->delete($id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Sincroniza permissions del rol.
     * POST /api/roles/{role_id}/permissions
     *
     * Reemplaza todas las permissions: permission_ids[].
     * Invalida cache Spatie.
     *
     * @param Request $request
     * @param string $role_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncPermissions(Request $request, string $role_id)
    {
        $data = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer'],
        ]);

        $response = $this->roleService->syncPermissions($role_id, $data['permission_ids']);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }
}
