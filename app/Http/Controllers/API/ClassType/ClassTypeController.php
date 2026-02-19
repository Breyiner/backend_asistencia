<?php

namespace App\Http\Controllers\API\ClassType;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassType\StoreClassTypeRequest;
use App\Http\Requests\ClassType\UpdateClassTypeRequest;
use App\Services\ClassType\ClassTypeService;

/**
 * Controlador REST para la gestión de tipos de clase.
 */
class ClassTypeController extends Controller
{
    /**
     * Servicio de lógica de negocio para tipos de clase.
     *
     * @var ClassTypeService
     */
    protected $classTypeService;

    /**
     * Inyección de dependencias del servicio de tipos de clase.
     *
     * @param ClassTypeService $classTypeService
     */
    public function __construct(ClassTypeService $classTypeService)
    {
        // Asigna el servicio a la propiedad protegida.
        $this->classTypeService = $classTypeService;
    }

    /**
     * Lista todos los tipos de clase.
     *
     * GET /api/class-types
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Obtiene todos los tipos de clase desde el servicio.
        $response = $this->classTypeService->getAll();

        // Si hay error, devuelve respuesta estandarizada de error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve listado de tipos de clase.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea un nuevo tipo de clase.
     *
     * POST /api/class-types
     *
     * @param  StoreClassTypeRequest  $request  Datos del tipo validados.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreClassTypeRequest $request)
    {
        // Crea el tipo usando los datos validados.
        $response = $this->classTypeService->create($request->validated());

        // Devuelve error si el servicio lo indica (por ejemplo, code duplicado).
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve el tipo de clase creado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Muestra el detalle de un tipo de clase.
     *
     * GET /api/class-types/{id}
     *
     * @param  int  $id  ID del tipo de clase.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        // Obtiene el tipo de clase por ID desde el servicio.
        $response = $this->classTypeService->getById($id);

        // Si no se encuentra o hay error, responde con error estándar.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve los datos del tipo de clase.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza un tipo de clase existente.
     *
     * PUT /api/class-types/{id}
     *
     * @param  UpdateClassTypeRequest  $request  Datos validados a actualizar.
     * @param  int                     $id       ID del tipo de clase.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateClassTypeRequest $request, int $id)
    {
        // Actualiza el tipo usando el servicio.
        $response = $this->classTypeService->update($request->validated(), $id);

        // Si hay errores de validación o negocio, responde con error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve el tipo de clase actualizado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina un tipo de clase.
     *
     * DELETE /api/class-types/{id}
     *
     * @param  int  $id  ID del tipo a eliminar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        // Solicita al servicio eliminar el tipo (con sus validaciones).
        $response = $this->classTypeService->delete($id);

        // Si no se puede eliminar (dependencias, tipo protegido, etc.), devuelve error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve confirmación de eliminación.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
