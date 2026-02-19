<?php

namespace App\Http\Controllers\API\Shift;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Services\Shift\ShiftService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **Jornadas (Shift)**.
 *
 * Modelo mínimo: name (MAÑANA/TARDE/NOCHE/MIXTA) → hasMany fichas.
 * Agrupa fichas por horarios. fichas.shift_id.
 *
 * **Endpoints clave**: index (fichas_count), CRUD (bloquea fichas>0).
 *
 * @see ShiftService Unique name/fichas_count
 */
class ShiftController extends Controller
{
    /**
     * Servicio de shifts.
     */
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Lista shifts (fichas_count).
     * GET /api/shifts
     *
     * Query: search, with_fichas. Sin paginación (4-6 shifts).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->shiftService->getAll();

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
     * Crea shift (name único).
     * POST /api/shifts
     *
     * Solo: name (MAÑANA/TARDE/etc). fichas_count=0 inicial.
     *
     * @param StoreShiftRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreShiftRequest $request)
    {
        $response = $this->shiftService->create($request->validated());

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
     * Detalle shift + fichas.
     * GET /api/shifts/{id}
     *
     * Incluye: fichas (lista), active_fichas_count.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $response = $this->shiftService->getById($id);

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
     * Actualiza name (único except actual).
     * PUT /api/shifts/{id}
     *
     * Impacta fichas reportes. Cascade update.
     *
     * @param UpdateShiftRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateShiftRequest $request, int $id)
    {
        $response = $this->shiftService->update($request->validated(), $id);

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
     * Elimina shift (bloquea fichas_count>0).
     * DELETE /api/shifts/{id}
     *
     * Error 409 con fichas_count/sugerencia reasignar.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $response = $this->shiftService->delete($id);

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
