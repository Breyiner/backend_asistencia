<?php

namespace App\Http\Controllers\API\Permission;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\Permission\PermissionService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **Permisos del Sistema**.
 *
 * Listado optimizado para asignación a roles (Spatie). Agrupados por módulo.
 * Formato `{recurso}.{acción}` (fichas.view, attendances.register, etc).
 *
 * **Endpoint único**: select (para formularios roles).
 *
 * @see PermissionService Agrupación por módulo y cache
 */
class PermissionController extends Controller
{
    /**
     * Servicio de permisos.
     */
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Permisos agrupados para select (asignación roles).
     * GET /api/permissions/select
     *
     * Grupos: Fichas, Clases, Asistencias, Usuarios, Reportes. Sin paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function select(Request $request)
    {
        $response = $this->permissionService->select();

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
