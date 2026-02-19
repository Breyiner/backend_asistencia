<?php

namespace App\Http\Controllers\API\Schedule;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Services\Schedule\ScheduleService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **Schedules** (plantillas semanales).
 *
 * Modelo mínimo: description + ficha_term_id → genera ScheduleSessions.
 * Flujo: Schedule → hasMany ScheduleSessions → RealClass.
 *
 * **Endpoints clave**: index, byFichaTerm (planificación trimestre), CRUD.
 *
 * @see ScheduleService Genera sessions + cascade delete
 */
class ScheduleController extends Controller
{
    /**
     * Servicio de schedules.
     */
    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Lista schedules (ficha_term, sessions_count).
     * GET /api/schedules
     *
     * Query: ficha_term_id, search. Sin paginación (pocos).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->scheduleService->getAll();

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
     * Detalle schedule + sessions.
     * GET /api/schedules/{id}
     *
     * Incluye: fichaTerm (ficha/term), schedule_sessions completas.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $response = $this->scheduleService->getById($id);

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
     * Schedules por FichaTerm (plan trimestre).
     * GET /api/schedules/ficha-term/{fichaTermId}
     *
     * Lista completa planificación trimestre.
     *
     * @param int $fichaTermId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByFichaTerm(int $fichaTermId)
    {
        $response = $this->scheduleService->getByFichaTermId($fichaTermId);

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
     * Crea schedule mínimo (genera ScheduleSessions).
     * POST /api/schedules
     *
     * Solo: description, ficha_term_id. Service genera sessions.
     *
     * @param StoreScheduleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreScheduleRequest $request)
    {
        $response = $this->scheduleService->create($request->validated());

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
     * Actualiza fillable (description, ficha_term_id).
     * PUT /api/schedules/{id}
     *
     * NO afecta ScheduleSessions existentes.
     *
     * @param UpdateScheduleRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateScheduleRequest $request, int $id)
    {
        $response = $this->scheduleService->update($request->validated(), $id);

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
     * Elimina schedule + CASCADE ScheduleSessions.
     * DELETE /api/schedules/{id}
     *
     * Bloquea si sessions tienen RealClass. Orfana RealClass.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $response = $this->scheduleService->delete($id);

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
