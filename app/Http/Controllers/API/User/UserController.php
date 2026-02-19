<?php

namespace App\Http\Controllers\API\User;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateOwnUserPasswordRequest;
use App\Http\Requests\User\UpdateRolesUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador REST API para **User** (Usuarios SENA).
 *
 * Fillable: document_number, email, password, document_type_id, status_id.
 * Traits: HasApiTokens, SoftDeletes, HasRoles (Spatie), HasChildren.
 * Relaciones: documentType, status, profile, fichas, instructorRealClasses, coordinatedTrainingPrograms.
 * 
 * **Endpoints clave**: index (paginación/filtros), role/{id}, me (perfil propio), CRUD + password propio.
 * 
 * @see UserService Roles Spatie/complejos instructor/coordinador/notificaciones
 */
class UserController extends Controller
{
    /**
     * Servicio de usuarios.
     */
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista usuarios (roles, fichas_count, real_classes_count).
     * GET /api/users
     *
     * Query: per_page, role_id, status_id, search. Con paginación.
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
     * Lista usuarios por rol específico.
     * GET /api/users/role/{role_id}
     *
     * Útil: admin "todos INSTRUCTORES", coordinador "mis instructores".
     *
     * @param int $role_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexByRole(int $role_id)
    {
        $response = $this->service->getAllByRoleId($role_id);

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
     * Detalle usuario + relaciones completas.
     * GET /api/users/{id}
     *
     * Incluye: roles, permissions, profile, fichas, coordinated_training_programs, estadísticas.
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
     * Perfil propio del usuario autenticado.
     * GET /api/users/me
     *
     * Incluye: auth_data, roles detallados, notifications, fichas_count.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showOwn(Request $request)
    {
        $user = Auth::user();
        $response = $this->service->getById($user->id);

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
     * Crea usuario (hash password, email verify).
     * POST /api/users
     *
     * Valida: document_number único, email único, document_type_id existe.
     *
     * @param StoreUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request)
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
     * Actualiza usuario (admin/coordinador).
     * PUT /api/users/{id}
     *
     * NO cambia password. Solo datos básicos + status.
     *
     * @param UpdateUserRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request, string $id)
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
     * Cambio password propio (verifica actual).
     * PUT /api/users/me/password
     *
     * Valida: current_password correcto, nuevo diferente, confirmación.
     * Efecto: logout tokens Sanctum.
     *
     * @param UpdateOwnUserPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOwnPassword(UpdateOwnUserPasswordRequest $request)
    {
        $user = Auth::user();
        $response = $this->service->updatePassword($request->validated(), $user->id);

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
     * Elimina usuario (SoftDeletes).
     * DELETE /api/users/{id}
     *
     * Bloquea: ADMIN, usuarios con fichas activas. Preserva histórico.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->service->destroy($id);

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
