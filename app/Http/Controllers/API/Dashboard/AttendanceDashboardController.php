<?php

namespace App\Http\Controllers\API\Dashboard;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AttendanceDashboardRequest;
use App\Services\Dashboard\AttendanceDashboardService;

/**
 * Controlador invocable para el dashboard de asistencias.
 *
 * Provee datos consolidados (resumen, estados, tendencias, top fichas y alertas)
 * para el dashboard principal, aplicando filtros y alcance según el rol del usuario.
 */
class AttendanceDashboardController extends Controller
{
    /**
     * Servicio encargado de construir el dashboard de asistencias.
     *
     * @var AttendanceDashboardService
     */
    public function __construct(private AttendanceDashboardService $service) {}

    /**
     * Retorna los datos del dashboard de asistencias según los filtros enviados.
     *
     * POST /api/dashboard/attendance
     *
     * Recibe filtros validados por AttendanceDashboardRequest, delega la generación
     * de métricas al servicio y responde usando el formateador estándar.
     *
     * @param  AttendanceDashboardRequest  $request  Filtros validados para el dashboard.
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(AttendanceDashboardRequest $request)
    {
        // Ejecuta la lógica de obtención del dashboard con los filtros recibidos.
        $result = $this->service->get($request->validated());

        // Si el servicio indica error (permisos, validaciones, etc.), retorna error.
        if (!empty($result['error'])) {
            return ResponseFormatter::error($result['message'] ?? 'Error', $result['code']);
        }

        // Devuelve datos del dashboard, paginación opcional y metadatos.
        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            $result['data'] ?? [],
            $result['paginate'] ?? [],
            $result['meta'] ?? []
        );
    }
}
