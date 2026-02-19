<?php

namespace App\Http\Controllers\API\ScheduleSession;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleSession\StoreScheduleSessionRequest;
use App\Http\Requests\ScheduleSession\UpdateScheduleSessionRequest;
use App\Services\ScheduleSession\ScheduleSessionService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **ScheduleSessions**.
 *
 * Fillable: instructor_id, schedule_id, time_slot_id, classroom_id, day_id, start_time, end_time.
 * → realClasses(). Puente Schedule → RealClass.
 *
 * **Endpoints clave**: index (disponibles), byFicha (calendario), CRUD manual.
 *
 * @see ScheduleSessionService Duplicados/disponibilidad/realClasses exists
 */
class ScheduleSessionController extends Controller
{
    /**
     * Servicio de schedule sessions.
     */
    protected ScheduleSessionService $scheduleSessionService;

    public function __construct(ScheduleSessionService $scheduleSessionService)
    {
        $this->scheduleSessionService = $scheduleSessionService;
    }

    /**
     * Lista sessions disponibles (has_real_class=false).
     * GET /api/schedule-sessions
     *
     * Filtros: ficha_id, date_from, has_real_class. Sin paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->scheduleSessionService->getAll();

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
     * Crea session manual (excepciones/festivos).
     * POST /api/schedule-sessions
     *
     * Valida: NO duplicado schedule_id+fecha+horario, instructor/aula libres.
     *
     * @param StoreScheduleSessionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreScheduleSessionRequest $request)
    {
        $response = $this->scheduleSessionService->create($request->validated());

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
     * Detalle session + relaciones/realClasses.
     * GET /api/schedule-sessions/{id}
     *
     * Incluye: instructor/schedule/time_slot/classroom/day, is_available.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $response = $this->scheduleSessionService->getById($id);

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
     * Sessions por ficha (calendario).
     * GET /api/schedule-sessions/ficha/{ficha_id}
     *
     * Agrupado día+hora. Para dashboard aprendiz/coordinador.
     *
     * @param int $ficha_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByFicha(int $ficha_id)
    {
        $response = $this->scheduleSessionService->getByFichaId($ficha_id);

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
     * Actualiza fillable (instructor/classroom/horario).
     * PUT /api/schedule-sessions/{id}
     *
     * Bloquea si realClasses exists.
     *
     * @param UpdateScheduleSessionRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateScheduleSessionRequest $request, int $id)
    {
        $response = $this->scheduleSessionService->update($request->validated(), $id);

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
     * Elimina session (si !realClasses exists).
     * DELETE /api/schedule-sessions/{id}
     *
     * Libera para reutilizar en RealClass.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $response = $this->scheduleSessionService->delete($id);

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
