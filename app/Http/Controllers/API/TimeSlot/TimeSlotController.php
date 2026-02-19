<?php

namespace App\Http\Controllers\API\TimeSlot;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimeSlot\StoreTimeSlotRequest;
use App\Http\Requests\TimeSlot\UpdateTimeSlotRequest;
use App\Services\TimeSlot\TimeSlotService;

/**
 * Controlador REST API para **Franjas Horarias (TimeSlot)**.
 *
 * Fillable: code, name, start_time, end_time → hasMany realClasses.
 * Valida solapamientos. Bloques institucionales (MAN1 06:00-08:00).
 *
 * **Endpoints clave**: index (ocupación), CRUD (bloquea realClasses>0).
 *
 * @see TimeSlotService Solapamientos/real_classes_count
 */
class TimeSlotController extends Controller
{
    /**
     * Servicio de time slots.
     */
    protected TimeSlotService $service;

    public function __construct(TimeSlotService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista time slots (duration_hours, real_classes_count).
     * GET /api/time-slots
     *
     * Query: is_morning, code, with_real_classes. Sin paginación.
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
     * Crea time slot (valida solapamiento).
     * POST /api/time-slots
     *
     * Valida: code único, start_time < end_time, NO overlap.
     *
     * @param StoreTimeSlotRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTimeSlotRequest $request)
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
     * Detalle time slot + realClasses.
     * GET /api/time-slots/{id}
     *
     * Incluye: real_classes (lista), avg_attendance, classrooms_used.
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
     * Actualiza time slot (valida solapamiento).
     * PUT /api/time-slots/{id}
     *
     * Impacta TODAS realClasses. Bloquea solapamientos.
     *
     * @param UpdateTimeSlotRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTimeSlotRequest $request, string $id)
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
     * Elimina time slot (bloquea real_classes_count>0).
     * DELETE /api/time-slots/{id}
     *
     * Error 409 con real_classes_count/sugerencia reasignar.
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
