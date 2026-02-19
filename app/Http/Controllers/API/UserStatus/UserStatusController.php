<?php

namespace App\Http\Controllers\API\UserStatus;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserStatus\PartialUpdateStatusRequest;
use App\Http\Requests\UserStatus\StoreStatusRequest;
use App\Http\Requests\UserStatus\UpdateStatusRequest;
use App\Services\UserStatus\UserStatusService;

/**
 * Controlador REST API para **UserStatus** (Estados de usuario).
 *
 * Catálogo: ACTIVO, INACTIVO, SUSPENDIDO. belongsTo User (many-to-one).
 * 
 * **Endpoints clave**: CRUD completo + PATCH parcial (is_active).
 * 
 * @see UserStatusService Validaciones uso/usuarios_asignados
 */
class UserStatusController extends Controller
{
    /**
     * Servicio de estados de usuario.
     */
    protected UserStatusService $service;

    public function __construct(UserStatusService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista todos los estados (para selects).
     * GET /api/user-status
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
     * Detalle estado por ID.
     * GET /api/user-status/{id}
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->service->getStatus($id);

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
     * Crea nuevo estado.
     * POST /api/user-status
     *
     * Valida: name único, code único.
     *
     * @param StoreStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreStatusRequest $request)
    {
        $data = $request->validated();
        $response = $this->service->createStatus($data);

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
     * Actualiza estado completo.
     * PUT /api/user-status/{id}
     *
     * @param UpdateStatusRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateStatusRequest $request, string $id)
    {
        $data = $request->validated();
        $response = $this->service->updateStatus($data, $id);

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
     * Actualización parcial (solo is_active).
     * PATCH /api/user-status/{id}
     *
     * Útil: activar/desactivar sin cambiar name/code.
     *
     * @param PartialUpdateStatusRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function partialUpdate(PartialUpdateStatusRequest $request, string $id)
    {
        $data = $request->validated();
        $response = $this->service->partialUpdateStatus($data, $id);

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
     * Elimina estado (bloquea si usuarios_asignados>0).
     * DELETE /api/user-status/{id}
     *
     * Error 409 con usuarios_count/sugerencia reasignar.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->service->deleteStatus($id);

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