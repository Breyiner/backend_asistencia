<?php

namespace App\Http\Controllers\API\Classroom;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classroom\StoreClassroomRequest;
use App\Http\Requests\Classroom\UpdateClassroomRequest;
use App\Services\Classroom\ClassroomService;
use Illuminate\Http\Request;

/**
 * Controlador REST para la gestión de aulas/salones.
 */
class ClassroomController extends Controller
{
    /**
     * Servicio de lógica de negocio para aulas.
     *
     * @var ClassroomService
     */
    protected $classroomService;

    /**
     * Inyección de dependencias del servicio de aulas.
     *
     * @param ClassroomService $classroomService
     */
    public function __construct(ClassroomService $classroomService)
    {
        // Asigna el servicio a la propiedad protegida.
        $this->classroomService = $classroomService;
    }

    /**
     * Lista todas las aulas del sistema.
     *
     * GET /api/classrooms
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Obtiene todas las aulas desde el servicio.
        $response = $this->classroomService->getAll();

        // Si el servicio reporta error, devuelve respuesta estandarizada de error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve listado de aulas en el campo data.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea una nueva aula.
     *
     * POST /api/classrooms
     *
     * @param  StoreClassroomRequest  $request  Datos del aula validados.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreClassroomRequest $request)
    {
        // Crea el aula usando los datos ya validados por el FormRequest.
        $response = $this->classroomService->create($request->validated());

        // Devuelve error si el servicio lo indica (ej. código duplicado).
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve el aula creada.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Muestra el detalle de un aula.
     *
     * GET /api/classrooms/{id}
     *
     * @param  int  $id  ID del aula.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        // Obtiene el aula específica con su información desde el servicio.
        $response = $this->classroomService->getById($id);

        // Si no se encuentra o hay error, responde con formato de error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve la información del aula.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza los datos de un aula existente.
     *
     * PUT /api/classrooms/{id}
     *
     * @param  UpdateClassroomRequest  $request  Datos validados a actualizar.
     * @param  int                     $id       ID del aula.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateClassroomRequest $request, int $id)
    {
        // Actualiza el aula con los datos proporcionados.
        $response = $this->classroomService->update($request->validated(), $id);

        // Si hay errores de validación o de negocio, responde con error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve el aula actualizada.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina (soft delete) un aula del sistema.
     *
     * DELETE /api/classrooms/{id}
     *
     * @param  int  $id  ID del aula a eliminar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        // Solicita al servicio eliminar (soft delete) el aula indicada.
        $response = $this->classroomService->delete($id);

        // Si hay conflicto (clases futuras, no encontrada, etc.), devuelve error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve confirmación de eliminación.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
