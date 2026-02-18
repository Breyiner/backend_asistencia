<?php

namespace App\Http\Controllers\API\NoClassDay;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\NoClassDay\CheckNoClassDayRequest;
use App\Http\Requests\NoClassDay\StoreNoClassDayRequest;
use App\Http\Requests\NoClassDay\UpdateNoClassDayRequest;
use App\Services\NoclassDay\NoClassDayService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **Días sin Clase** (festivos, vacaciones, eventos).
 *
 * Gestiona días no laborables institucionales o por ficha. 
 * Valida disponibilidad antes de crear clases.
 *
 * **Tipos**: FESTIVO_NACIONAL, VACACIONES, PARO, EVENTO_INSTITUCIONAL
 * **Alcance**: institucional (ficha_id=null) o por ficha específica
 *
 * @see NoClassDayService Validaciones y carga festivos
 * @see CheckNoClassDayRequest Validación disponibilidad
 */
class NoClassDayController extends Controller
{
    /**
     * Servicio de días sin clase.
     */
    protected NoClassDayService $noClassDayService;

    public function __construct(NoClassDayService $noClassDayService)
    {
        $this->noClassDayService = $noClassDayService;
    }

    /**
     * Listado paginado de días sin clase.
     * GET /api/no-class-days
     *
     * Filtros: date_from/to, ficha_id, type, is_national, upcoming
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->noClassDayService->getAll($request->get('per_page', 10));

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
     * Detalle de día sin clase con fichas/clases afectadas.
     * GET /api/no-class-days/{id}
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->noClassDayService->getById($id);

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
     * Crear día sin clase.
     * POST /api/no-class-days
     *
     * Valida: sin duplicados, permisos por alcance, festivos únicos.
     *
     * @param StoreNoClassDayRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreNoClassDayRequest $request)
    {
        $response = $this->noClassDayService->store($request->validated());

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
     * Actualizar día sin clase.
     * PUT /api/no-class-days/{id}
     *
     * Restricciones: no modificar festivos nacionales, validar alcance.
     *
     * @param UpdateNoClassDayRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNoClassDayRequest $request, string $id)
    {
        $response = $this->noClassDayService->update($id, $request->validated());

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
     * Eliminar día sin clase.
     * DELETE /api/no-class-days/{id}
     *
     * Protecciones: no eliminar festivos nacionales, validar permisos.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->noClassDayService->destroy($id);

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
     * Verificar disponibilidad de fecha para clase.
     * POST /api/no-class-days/check
     *
     * Valida: días sin clase, fines de semana, rango ficha.
     * Retorna is_available + motivo bloqueo.
     *
     * @param CheckNoClassDayRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(CheckNoClassDayRequest $request)
    {
        $response = $this->noClassDayService->checkByFichaAndDate(
            $request->validated('ficha_id'),
            $request->validated('date')
        );

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
