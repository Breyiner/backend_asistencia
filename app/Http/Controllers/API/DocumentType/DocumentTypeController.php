<?php

namespace App\Http\Controllers\API\DocumentType;

use App\Helpers\ResponseFormatter;
use App\Http\Requests\DocumentType\StoreDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateDocumentTypeRequest;
use App\Http\Requests\DocumentType\PartialUpdateDocumentTypeRequest;
use App\Http\Controllers\Controller;
use App\Services\DocumentType\DocumentTypeService;

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
        // Asigna el servicio para usarlo en los métodos del controlador.
        $this->service = $service;
    }

    /**
     * Lista todos los tipos de documento.
     *
     * GET /api/document-types
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Obtiene todos los tipos desde el servicio.
        $response = $this->service->getAll();

        // Si hay error, responde con el formato estándar.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Devuelve listado de tipos de documento.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Muestra el detalle de un tipo de documento.
     *
     * GET /api/document-types/{id}
     *
     * @param  string  $id  ID del tipo de documento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        // Busca el tipo por ID usando el servicio.
        $response = $this->service->getById($id);

        // Si no existe o hubo problema, responde con error.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Devuelve datos completos del tipo de documento.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea un nuevo tipo de documento.
     *
     * POST /api/document-types
     *
     * @param  StoreDocumentTypeRequest  $request  Datos validados del tipo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDocumentTypeRequest $request)
    {
        // Datos ya validados por el FormRequest.
        $data = $request->validated();

        // Crea el tipo usando el servicio (incluye validación de regex, unicidad, etc.).
        $response = $this->service->create($data);

        // Si el servicio devuelve error, responde en formato estándar.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Devuelve el tipo de documento creado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza completamente un tipo de documento (PUT).
     *
     * PUT /api/document-types/{id}
     *
     * @param  UpdateDocumentTypeRequest  $request  Datos completos validados.
     * @param  string                     $id       ID del tipo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDocumentTypeRequest $request, string $id)
    {
        // Datos validados para actualización completa.
        $data = $request->validated();

        // Delega la actualización al servicio.
        $response = $this->service->update($data, $id);

        // Si hay conflicto o error de validación, responde con error.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Devuelve el tipo de documento actualizado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza parcialmente un tipo de documento (PATCH).
     *
     * PATCH /api/document-types/{id}
     *
     * @param  PartialUpdateDocumentTypeRequest  $request  Campos parciales validados.
     * @param  string                            $id       ID del tipo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function partialUpdate(PartialUpdateDocumentTypeRequest $request, string $id)
    {
        // Solo campos enviados y validados.
        $data = $request->validated();

        // Actualización parcial delegada al servicio.
        $response = $this->service->partialUpdate($data, $id);

        // Si hay error, responde con el formato estándar.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Devuelve el tipo de documento actualizado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina (normalmente soft-delete) un tipo de documento.
     *
     * DELETE /api/document-types/{id}
     *
     * @param  string  $id  ID del tipo a eliminar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        // Solicita al servicio eliminar el tipo (con sus validaciones).
        $response = $this->service->delete($id);

        // Si no puede eliminarse (usuarios asociados, tipo protegido, etc.), devuelve error.
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Devuelve confirmación de eliminación.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
