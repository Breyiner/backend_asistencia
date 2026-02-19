<?php

namespace App\Http\Controllers\API\NoClassReason;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\NoClassReason\StoreNoClassReasonRequest;
use App\Http\Requests\NoClassReason\UpdateNoClassReasonRequest;
use App\Services\NoClassReason\NoClassReasonService;

/**
 * Controlador REST API para **Motivos de No Clase**.
 *
 * Catálogo de razones para cancelar clases programadas (INSTRUCTOR_AUSENTE, 
 * FALLA_INFRAESTRUCTURA, REPROGRAMADA). Diferente de NoClassDay (preventivo).
 *
 * **Flags clave**: requires_justification, is_justifiable, affects_instructor_rating
 *
 * @see NoClassReasonService Validaciones y estadísticas uso
 * @see StoreNoClassReasonRequest /\ UpdateNoClassReasonRequest Validaciones
 */
class NoClassReasonController extends Controller
{
    /**
     * Servicio de motivos no clase.
     */
    protected NoClassReasonService $noClassReasonService;

    public function __construct(NoClassReasonService $noClassReasonService)
    {
        $this->noClassReasonService = $noClassReasonService;
    }

    /**
     * Listado de motivos (sin paginación).
     * GET /api/no-class-reasons
     *
     * Filtros: is_justifiable, requires_justification, active, with_stats
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $response = $this->noClassReasonService->getAll();

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
     * Detalle de motivo con estadísticas de uso.
     * GET /api/no-class-reasons/{id}
     *
     * Incluye: clases asociadas, distribución instructor/ficha, tendencias.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->noClassReasonService->getById($id);

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
     * Crear motivo personalizado.
     * POST /api/no-class-reasons
     *
     * Solo admins. Valida: code único, lógica flags.
     *
     * @param StoreNoClassReasonRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreNoClassReasonRequest $request)
    {
        $response = $this->noClassReasonService->store($request->validated());

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
     * Actualizar motivo existente.
     * PUT /api/no-class-reasons/{id}
     *
     * Restricciones: no modificar code predeterminados, validar impacto métricas.
     *
     * @param UpdateNoClassReasonRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNoClassReasonRequest $request, string $id)
    {
        $response = $this->noClassReasonService->update($id, $request->validated());

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
     * Eliminar motivo (solo sin clases asociadas).
     * DELETE /api/no-class-reasons/{id}
     *
     * Protecciones: no eliminar predeterminados, validar uso histórico.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->noClassReasonService->destroy($id);

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
