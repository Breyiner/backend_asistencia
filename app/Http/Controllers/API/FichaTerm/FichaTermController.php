<?php

namespace App\Http\Controllers\API\FichaTerm;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FichaTerm\StoreFichaTermRequest;
use App\Http\Requests\FichaTerm\UpdateFichaTermRequest;
use App\Services\FichaTerm\FichaTermService;

/**
 * Controlador REST API para **Períodos/Trimestres de Fichas**.
 *
 * Divide programas en bloques temporales (trimestres, cuatrimestres) para 
 * seguimiento de progreso, organización de competencias y evaluación por etapas.
 *
 * **Estados**: PROGRAMADO → EN_CURSO → FINALIZADO
 * **Validaciones clave**: fechas sin solapamiento, solo un término is_current por ficha
 *
 * @see FichaTermService Lógica de validación y gestión is_current
 * @see StoreFichaTermRequest /\ UpdateFichaTermRequest Validaciones
 */
class FichaTermController extends Controller
{
    /**
     * Servicio de lógica de términos.
     */
    protected FichaTermService $fichaTermService;

    public function __construct(FichaTermService $fichaTermService)
    {
        $this->fichaTermService = $fichaTermService;
    }

    /**
     * Listado de términos académicos (sin paginación).
     * GET /api/ficha-terms
     *
     * Filtros: ficha_id, is_current, status, with_stats
     * Ordenamiento: ficha_id → number (cronológico)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $response = $this->fichaTermService->getAll();

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
     * Detalle de término con estadísticas y clases.
     * GET /api/ficha-terms/{id}
     *
     * Incluye: ficha asociada, competencias, clases, aprendices en riesgo.
     *
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $response = $this->fichaTermService->getById($id);

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
     * Crear nuevo término académico.
     * POST /api/ficha-terms
     *
     * Valida: fechas dentro rango ficha, sin solapamientos, secuencia number.
     * Si is_current=true, desactiva otros términos.
     *
     * @param StoreFichaTermRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreFichaTermRequest $request)
    {
        $response = $this->fichaTermService->create($request->validated());

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
     * Actualizar término existente.
     * PUT /api/ficha-terms/{id}
     *
     * Restricciones: nuevas fechas deben incluir clases existentes,
     * validar solapamientos.
     *
     * @param UpdateFichaTermRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFichaTermRequest $request, int $id)
    {
        $response = $this->fichaTermService->update($request->validated(), $id);

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
     * Establecer término como actual (operación atómica).
     * PATCH /api/ficha-terms/{id}/current
     *
     * Desactiva otros términos de la ficha, activa este.
     * Valida: ficha ACTIVA, término no FINALIZADO.
     *
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setCurrent($id)
    {
        $response = $this->fichaTermService->setCurrent($id);

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
     * Eliminar término (soft/hard delete).
     * DELETE /api/ficha-terms/{id}
     *
     * Validaciones estrictas: sin clases asociadas, no is_current,
     * preferir CANCELADO sobre eliminación.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->fichaTermService->delete($id);

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
