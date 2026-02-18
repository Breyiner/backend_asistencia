<?php

namespace App\Http\Controllers\API\Phase;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Phase\StorePhaseRequest;
use App\Http\Requests\Phase\UpdatePhaseRequest;
use App\Services\Phase\PhaseService;

/**
 * Controlador REST API para **Fases SENA**.
 *
 * Catálogo: ETAPA_LECTIVA (75%), ETAPA_PRODUCTIVA (24%), INDUCCION, CERTIFICACION.
 * Determina asistencia/clases/práctica. Protegidas (no eliminar con aprendices).
 *
 * **Endpoints clave**: index (selects), CRUD admins.
 *
 * @see PhaseService Validaciones SENA/unicidad/aprendices
 * @see StorePhaseRequest / UpdatePhaseRequest Validaciones porcentajes/flags
 */
class PhaseController extends Controller
{
    /**
     * Servicio de fases.
     */
    protected PhaseService $phaseService;

    public function __construct(PhaseService $phaseService)
    {
        $this->phaseService = $phaseService;
    }

    /**
     * Lista fases (orden por secuencia).
     * GET /api/phases
     *
     * Filtros: requires_attendance, allows_classes, is_practical, active. Sin paginación.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $response = $this->phaseService->getAll();

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
     * Detalle fase con estadísticas.
     * GET /api/phases/{id}
     *
     * Stats: apprentices_count, by_ficha, transition_rate, avg_attendance.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->phaseService->getById($id);

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
     * Crea fase personalizada (admins).
     * POST /api/phases
     *
     * Valida: code único UPPER_CASE, duration_percentage (0-100), flags lógicos.
     *
     * @param StorePhaseRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePhaseRequest $request)
    {
        $response = $this->phaseService->create($request->validated());

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
     * Actualiza fase (no code en SENA predeterminadas).
     * PUT /api/phases/{id}
     *
     * Invalida cache. Afecta lógica asistencia/clases futuras.
     *
     * @param UpdatePhaseRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePhaseRequest $request, string $id)
    {
        $response = $this->phaseService->update($request->validated(), $id);

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
     * Elimina fase sin aprendices.
     * DELETE /api/phases/{id}
     *
     * Bloquea: SENA predeterminadas, apprentices_count>0. Sugiere active=false.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->phaseService->delete($id);

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
