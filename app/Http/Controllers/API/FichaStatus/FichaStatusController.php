<?php

namespace App\Http\Controllers\API\FichaStatus;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FichaStatus\StoreFichaStatusRequest;
use App\Http\Requests\FichaStatus\UpdateFichaStatusRequest;
use App\Services\FichaStatus\FichaStatusService;

/**
 * Controlador REST API para catálogo de **Estados de Fichas**.
 *
 * Gestiona estados del ciclo de vida de fichas (EN_INSCRIPCION, ACTIVA, 
 * SUSPENDIDA, FINALIZADA, CANCELADA) y sus transiciones permitidas.
 *
 * **Estados predeterminados**: EN_INSCRIPCION → ACTIVA → [SUSPENDIDA/FINALIZADA/CANCELADA]
 * **Configuración por estado**: allows_classes, allows_enrollment, is_terminal, next_states
 *
 * @see FichaStatusService Lógica de validación y transiciones
 * @see StoreFichaStatusRequest /\ UpdateFichaStatusRequest Validaciones
 */
class FichaStatusController extends Controller
{
    /**
     * Servicio de lógica de estados.
     */
    protected FichaStatusService $statusService;

    public function __construct(FichaStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Listado completo de estados (sin paginación).
     * GET /api/ficha-statuses
     *
     * Retorna catálogo ordenado por flujo natural.
     * Filtros: is_active, is_terminal, allows_classes, with_stats
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $response = $this->statusService->getAll();

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
     * Detalle de estado con estadísticas de uso.
     * GET /api/ficha-statuses/{id}
     *
     * Incluye: fichas asociadas, transiciones comunes, distribución por programa.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->statusService->getStatus($id);

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
     * Crear estado personalizado.
     * POST /api/ficha-statuses
     *
     * Valida: code único, color hexadecimal, lógica is_terminal/next_states.
     *
     * @param StoreFichaStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreFichaStatusRequest $request)
    {
        $response = $this->statusService->createStatus($request->validated());

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
     * Actualizar estado existente.
     * PUT /api/ficha-statuses/{id}
     *
     * Restricciones: no modificar code de estados predeterminados, 
     * validar impacto en fichas activas.
     *
     * @param UpdateFichaStatusRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFichaStatusRequest $request, string $id)
    {
        $response = $this->statusService->updateStatus($request->validated(), $id);

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
     * Eliminar estado personalizado (hard delete).
     * DELETE /api/ficha-statuses/{id}
     *
     * Validaciones estrictas: sin fichas asociadas, no predeterminado, 
     * sin referencias en next_states.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->statusService->deleteStatus($id);

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
  