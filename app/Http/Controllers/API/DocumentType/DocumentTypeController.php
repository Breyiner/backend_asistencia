<?php

namespace App\Http\Controllers\API\DocumentType;

use App\Helpers\ResponseFormatter;
use App\Http\Requests\DocumentType\StoreDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateDocumentTypeRequest;
use App\Http\Controllers\Controller;
use App\Services\DocumentType\DocumentTypeService;
use Illuminate\Http\Request;

/**
 * Controlador REST para la gestión de tipos de documento.
 *
 * Administra el catálogo de documentos de identificación (CC, TI, CE, PAS, etc.)
 * que se usan en el registro y validación de usuarios.
 */
class DocumentTypeController extends Controller
{
    /**
     * Servicio de lógica de negocio para tipos de documento.
     *
     * @var DocumentTypeService
     */
    protected $service;

    /**
     * Inyección de dependencias del servicio de tipos de documento.
     *
     * @param DocumentTypeService $service
     */
    public function __construct(DocumentTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista los tipos de documento de forma paginada con filtros opcionales.
     *
     * GET /api/document-types
     *
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
            $response['paginate'] ?? []
        );
    }

    /**
     * Lista todos los tipos de documento en formato simplificado para selects/dropdowns.
     *
     * GET /api/document-types/select
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function select()
    {
        $response = $this->service->getAllForSelect();

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Muestra el detalle de un tipo de documento.
     *
     * GET /api/document-types/{id}
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->service->getById($id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea un nuevo tipo de documento.
     *
     * POST /api/document-types
     *
     * @param  StoreDocumentTypeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDocumentTypeRequest $request)
    {
        $response = $this->service->create($request->validated());

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza un tipo de documento (PUT/PATCH).
     *
     * PUT   /api/document-types/{id}
     * PATCH /api/document-types/{id}
     *
     * @param  UpdateDocumentTypeRequest  $request
     * @param  string                     $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDocumentTypeRequest $request, string $id)
    {
        $response = $this->service->update($request->validated(), $id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina un tipo de documento.
     *
     * DELETE /api/document-types/{id}
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->service->delete($id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
} 