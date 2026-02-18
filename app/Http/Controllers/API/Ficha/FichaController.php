<?php

namespace App\Http\Controllers\API\Ficha;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ficha\StoreFichaRequest;
use App\Http\Requests\Ficha\UpdateFichaRequest;
use App\Services\Ficha\FichaService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para gestión CRUD de **Fichas de Formación** (SENA).
 *
 * Las fichas representan cohortes de aprendices en un programa específico,
 * jornada y período. Centralizan horarios, clases y asistencias.
 *
 * **Estados**: EN_INSCRIPCION → ACTIVA → [SUSPENDIDA/FINALIZADA/CANCELADA]
 * **Jornadas**: DIURNA, NOCTURNA, MIXTA, FIN_SEMANA, VIRTUAL
 * **Código**: `{PROGRAMA}-{AÑO}{NUM}` (ej: ADSO-2690001)
 *
 * @see FichaService Lógica de negocio y scopes por rol
 * @see StoreFichaRequest /\ UpdateFichaRequest Validaciones
 */
class FichaController extends Controller
{
    /**
     * Servicio de lógica de fichas.
     */
    private FichaService $fichaService;

    public function __construct(FichaService $fichaService)
    {
        $this->fichaService = $fichaService;
    }

    /**
     * Listado paginado de fichas con filtros.
     * GET /api/fichas
     *
     * Filtros: status, jornada, area_id, program_id, search
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->fichaService->getAll($request->get('per_page', 10));

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
     * Fichas activas para dropdowns/selects.
     * GET /api/fichas/select
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function select()
    {
        $response = $this->fichaService->select();

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
     * Detalle completo de ficha con relaciones y estadísticas.
     * GET /api/fichas/{id}
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->fichaService->getById($id);

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
     * Fichas de un programa específico.
     * GET /api/fichas/program/{programId}
     *
     * @param string $programId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByTrainingProgram(string $programId)
    {
        $response = $this->fichaService->getByTrainingProgram($programId);

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
     * Fichas activas disponibles para crear clases.
     * GET /api/fichas/available-for-class
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableForRealClass()
    {
        $response = $this->fichaService->availableForRealClass();

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
     * Crear nueva ficha de formación.
     * POST /api/fichas
     *
     * @param StoreFichaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreFichaRequest $request)
    {
        $response = $this->fichaService->create($request->validated());

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
     * Actualizar ficha existente.
     * PUT /api/fichas/{id}
     *
     * @param UpdateFichaRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFichaRequest $request, string $id)
    {
        $response = $this->fichaService->update($id, $request->validated());

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
     * Eliminar ficha (soft delete).
     * DELETE /api/fichas/{id}
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->fichaService->delete($id);

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
