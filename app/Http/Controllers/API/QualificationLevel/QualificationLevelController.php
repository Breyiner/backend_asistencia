<?php

namespace App\Http\Controllers\API\QualificationLevel;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\QualificationLevel\StoreQualificationLevelRequest;
use App\Http\Requests\QualificationLevel\UpdateQualificationLevelRequest;
use App\Services\QualificationLevel\QualificationLevelService;

/**
 * Controlador REST API para **Niveles SENA (MNC)**.
 *
 * Catálogo MNC: AUXILIAR(2), TECNICO(4), TECNOLOGO(5). Horas mín/máx, certificación.
 * Protegidos (no eliminar con programas). training_programs.qualification_level_id.
 *
 * **Endpoints clave**: index (selects programas), CRUD admins.
 *
 * @see QualificationLevelService Validaciones MNC/horas/programas
 * @see StoreQualificationLevelRequest / UpdateQualificationLevelRequest Validaciones rangos
 */
class QualificationLevelController extends Controller
{
    /**
     * Servicio de niveles cualificación.
     */
    protected QualificationLevelService $service;

    public function __construct(QualificationLevelService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista niveles (orden jerárquico).
     * GET /api/qualification-levels
     *
     * Filtros: requires_high_school, mnc_level, active. Sin paginación.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $response = $this->service->getAll();

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
     * Detalle nivel con estadísticas.
     * GET /api/qualification-levels/{id}
     *
     * Stats: programs_count, by_area, total_apprentices, avg_duration.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->service->getById($id);

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
     * Crea nivel personalizado (admins).
     * POST /api/qualification-levels
     *
     * Valida: code único UPPER_CASE, minimum_hours < maximum_hours, mnc_level 0-8.
     *
     * @param StoreQualificationLevelRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreQualificationLevelRequest $request)
    {
        $response = $this->service->create($request->validated());

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
     * Actualiza nivel (no code en SENA predeterminados).
     * PUT /api/qualification-levels/{id}
     *
     * Invalida cache. Afecta validación programas futuros.
     *
     * @param UpdateQualificationLevelRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateQualificationLevelRequest $request, string $id)
    {
        $response = $this->service->update($request->validated(), $id);

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
     * Elimina nivel sin programas.
     * DELETE /api/qualification-levels/{id}
     *
     * Bloquea: SENA predeterminados, programs_count>0. Sugiere active=false.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->service->delete($id);

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
