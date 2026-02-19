<?php

namespace App\Http\Controllers\API\UserProfile;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserProfile\UpdateUserProfileRequest;
use App\Services\UserProfile\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador REST API para **UserProfile** (Perfiles de usuario).
 *
 * Maneja perfiles personales (first_name, last_name, phone, address, avatar).
 * belongsTo User (one-to-one).
 * 
 * **Endpoints clave**: index (admin), me (propio), user/{id} (admin), update propio/otro.
 * 
 * @see UserProfileService Obtener/actualizar perfiles
 */
class UserProfileController extends Controller
{
    /**
     * Servicio de perfiles de usuario.
     */
    protected UserProfileService $service;

    public function __construct(UserProfileService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista todos los perfiles (admin).
     * GET /api/user-profiles
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
     * Detalle perfil por ID.
     * GET /api/user-profiles/{id}
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->service->getProfile($id);

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
     * Perfil propio (autenticado).
     * GET /api/user-profiles/me
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showOwn()
    {
        $user = Auth::user();
        $response = $this->service->getProfile($user->id);

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
     * Perfil por ID de usuario.
     * GET /api/user-profiles/user/{user_id}
     *
     * @param string $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByUser(string $user_id)
    {
        $response = $this->service->getProfileByUser($user_id);

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
     * Actualiza perfil propio.
     * PUT /api/user-profiles/me
     *
     * Campos: first_name, last_name, phone, address, avatar.
     *
     * @param UpdateUserProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOwn(UpdateUserProfileRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        $response = $this->service->updateProfile($data, $user->id);

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
     * Actualiza perfil de usuario (admin).
     * PUT /api/user-profiles/user/{user_id}
     *
     * @param UpdateUserProfileRequest $request
     * @param string $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserProfileRequest $request, string $user_id)
    {
        $data = $request->validated();
        $response = $this->service->updateProfile($data, $user_id);

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
