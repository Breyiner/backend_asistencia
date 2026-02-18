<?php

namespace App\Http\Controllers\API\RealClass;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\RealClass\StoreRealClassRequest;
use App\Http\Requests\RealClass\UpdateRealClassRequest;
use App\Services\RealClass\RealClassService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **Clases Reales**.
 *
 * Ejecuciones concretas: schedule_session + instructor + aula + asistencias.
 * Estados: PROGRAMADA→EN_CURSO→FINALIZADA/CANCELADA. Base para attendances.
 *
 * **Endpoints clave**: index (admin), mine (instructor), managed (coordinador), CRUD.
 *
 * @see RealClassService Disponibilidad/estados/QR/notificaciones
 */
class RealClassController extends Controller
{
    /**
     * Servicio de clases reales.
     */
    protected RealClassService $realClassService;

    public function __construct(RealClassService $realClassService)
    {
        $this->realClassService = $realClassService;
    }

    /**
     * Lista clases (scopes por rol: admin→todas, coord→fichas).
     * GET /api/real-classes
     *
     * Filtros: ficha_id, status, date_from/to, per_page. Paginado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->realClassService->getAll($request, $request->get('per_page', 10));

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? []
        );
    }

    /**
     * Mis clases (instructor autenticado).
     * GET /api/real-classes/mine
     *
     * Filtros: status, date_from/to, today/upcoming. Paginado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mine(Request $request)
    {
        $response = $this->realClassService->getMine($request, $request->get('per_page', 10));

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? []
        );
    }

    /**
     * Clases gestionadas (coordinador: sus fichas).
     * GET /api/real-classes/managed
     *
     * Filtros: status, date_from/to. Paginado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function managed(Request $request)
    {
        $response = $this->realClassService->getManaged($request, $request->get('per_page', 10));

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? []
        );
    }

    /**
     * Detalle clase (asistencias/justificaciones).
     * GET /api/real-classes/{id}
     *
     * Incluye: attendances completas, pending_justifications, can_* flags.
     *
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $response = $this->realClassService->getById($id);

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
     * Crea clase real (valida disponibilidad instructor/aula).
     * POST /api/real-classes
     *
     * Genera QR, notifica aprendices/instructor. Status: PROGRAMADA.
     *
     * @param StoreRealClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRealClassRequest $request)
    {
        $response = $this->realClassService->create($request->validated());

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
     * Actualiza clase (bloquea si attendance_closed).
     * PUT /api/real-classes/{id}
     *
     * No cambia schedule_session. Valida instructor si asistencias.
     *
     * @param UpdateRealClassRequest $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRealClassRequest $request, $id)
    {
        $response = $this->realClassService->update($request->validated(), $id);

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
     * Elimina clase PROGRAMADA sin asistencias.
     * DELETE /api/real-classes/{id}
     *
     * Sugiere cancelar en lugar de eliminar.
     *
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $response = $this->realClassService->delete($id);

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
