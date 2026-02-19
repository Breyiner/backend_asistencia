<?php

namespace App\Http\Controllers\API\TrainingProgram;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingProgram\StoreTrainingProgramRequest;
use App\Http\Requests\TrainingProgram\UpdateTrainingProgramRequest;
use App\Services\TrainingProgram\TrainingProgramService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **TrainingProgram** (Programa de Formación).
 *
 * Fillable: name, description, duration, qualification_level_id, area_id, coordinator_id.
 * Relaciones: qualificationLevel, area, coordinator (User), fichas (hasMany), apprentices (hasManyThrough).
 * 
 * **Endpoints clave**: index (paginación/filtros), select (dropdowns), CRUD (bloquea fichas>0).
 * 
 * @see TrainingProgramService Conteos/validaciones coordinator/jerarquías
 */
class TrainingProgramController extends Controller
{
    /**
     * Servicio de training programs.
     */
    protected TrainingProgramService $service;

    public function __construct(TrainingProgramService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista programas (fichas_count, apprentices).
     * GET /api/training-programs
     *
     * Query: per_page, area_id, coordinator_id, search. Con paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->service->getAll($request->get('per_page', 10));

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate']
        );
    }

    /**
     * Lista simplificada para selects/dropdowns.
     * GET /api/training-programs/select
     *
     * Formato: value/label/fichas_count. Sin paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function select(Request $request)
    {
        $response = $this->service->getAllForSelect();

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
     * Detalle programa + relaciones completas.
     * GET /api/training-programs/{id}
     *
     * Incluye: qualification_level, area, coordinator, fichas[], estadísticas.
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
     * Crea programa (valida coordinator/unicidad).
     * POST /api/training-programs
     *
     * Valida: campos required, FKs existen, coordinator=COORDINADOR, name único.
     *
     * @param StoreTrainingProgramRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTrainingProgramRequest $request)
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
     * Actualiza programa (cascade a fichas).
     * PUT /api/training-programs/{id}
     *
     * Impacta TODAS fichas. Valida name único (excepto id), coordinator válido.
     *
     * @param UpdateTrainingProgramRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTrainingProgramRequest $request, string $id)
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
     * Elimina programa (bloquea fichas_count>0).
     * DELETE /api/training-programs/{id}
     *
     * Error 409 con fichas_count/apprentices_count/sugerencia archivar.
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
