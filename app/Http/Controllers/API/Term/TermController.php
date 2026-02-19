<?php

namespace App\Http\Controllers\API\Term;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Term\StoreTermRequest;
use App\Http\Requests\Term\UpdateTermRequest;
use App\Services\Term\TermService;
use Illuminate\Http\Request;

/**
 * Controlador REST API para **Períodos (Term)**.
 *
 * Modelo mínimo: name (T1-2026) → hasMany fichaTerms.
 * Agrupa FichaTerms por trimestre. is_current flag.
 *
 * **Endpoints clave**: index (ficha_terms_count), CRUD (bloquea fichaTerms>0).
 *
 * @see TermService Unique name/is_current/ficha_terms_count
 */
class TermController extends Controller
{
    /**
     * Servicio de terms.
     */
    protected TermService $termService;

    public function __construct(TermService $termService)
    {
        $this->termService = $termService;
    }

    /**
     * Lista terms (ficha_terms_count, is_current).
     * GET /api/terms
     *
     * Query: is_current, search, with_ficha_terms. Sin paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->termService->getAll();

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
     * Crea term (name único).
     * POST /api/terms
     *
     * Solo: name (Trimestre 1 2026). ficha_terms_count=0 inicial.
     *
     * @param StoreTermRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTermRequest $request)
    {
        $response = $this->termService->create($request->validated());

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
     * Detalle term + fichaTerms.
     * GET /api/terms/{id}
     *
     * Incluye: ficha_terms (lista), current_ficha_terms_count, total_apprentices.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->termService->getById($id);

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
     * PUT /api/terms/{id}
     *
     * Impacta fichaTerms reportes. Cascade update.
     *
     * @param UpdateTermRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTermRequest $request, string $id)
    {
        $response = $this->termService->update($request->validated(), $id);

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
     * Elimina term (bloquea ficha_terms_count>0).
     * DELETE /api/terms/{id}
     *
     * Error 409 con ficha_terms_count/sugerencia reasignar.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->termService->delete($id);

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
