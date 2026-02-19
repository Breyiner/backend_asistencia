<?php

namespace App\Http\Controllers\API\Classroom;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classroom\StoreClassroomRequest;
use App\Http\Requests\Classroom\UpdateClassroomRequest;
use App\Services\Classroom\ClassroomService;
use Illuminate\Http\Request;

/**
 * Controlador REST para la gestión de ambientes (aulas/salones).
 *
 * Los ambientes son espacios físicos o virtuales donde se dictan las clases.
 * Catálogo relativamente estable que se asigna a las programaciones de clases.
 *
 * Funcionalidades principales:
 * - CRUD completo de ambientes
 * - Endpoint optimizado para dropdowns/select (sin paginación)
 * - Validación de datos mediante Form Requests
 * - Respuestas estandarizadas con ResponseFormatter
 *
 * Relaciones del modelo Classroom (futuras):
 * - belongsToMany classes: Clases programadas en este ambiente
 * - hasMany schedules: Horarios asignados al ambiente
 *
 * Endpoints disponibles:
 * - GET    /api/classrooms          - Listado paginado de ambientes
 * - GET    /api/classrooms/select   - Listado completo para selects (sin paginación)
 * - GET    /api/classrooms/{id}     - Detalle de un ambiente específico
 * - POST   /api/classrooms          - Crear nuevo ambiente
 * - PUT    /api/classrooms/{id}     - Actualizar ambiente existente
 * - DELETE /api/classrooms/{id}     - Eliminar ambiente (soft delete)
 *
 * Permisos requeridos (configurados en routes/api.php):
 * - classrooms.view
 * - classrooms.create
 * - classrooms.update
 * - classrooms.delete
 *
 * Uso típico:
 * - Administradores crean y gestionan ambientes
 * - Coordinadores asignan ambientes a programaciones de clases
 * - Frontend consume /select para dropdowns de asignación
 */
class ClassroomController extends Controller
{
    /**
     * Servicio de lógica de negocio para ambientes.
     *
     * Maneja operaciones CRUD, validaciones de negocio y consultas complejas.
     *
     * @var ClassroomService
     */
    protected $classroomService;

    /**
     * Inyección de dependencias del servicio de ambientes.
     *
     * Laravel resuelve automáticamente la instancia de ClassroomService
     * desde el contenedor de servicios.
     *
     * @param ClassroomService $classroomService Servicio de ambientes
     */
    public function __construct(ClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }

    /**
     * Lista todos los ambientes con paginación.
     *
     * Endpoint: GET /api/classrooms
     *
     * Soporta filtros:
     * - ?classroom_name=... (busca en nombre)
     * - ?description=... (busca en descripción)
     * - ?per_page=15 (personaliza paginación)
     *
     * @param Request $request Petición HTTP con query parameters
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada con paginación
     */
    public function index(Request $request)
    {
        // Obtiene per_page del query string, default 10
        $response = $this->classroomService->getAll($request->get('per_page', 10));

        // Manejo de errores del servicio
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con datos paginados
        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? null
        );
    }

    /**
     * Lista todos los ambientes optimizado para componentes select/dropdown.
     *
     * Endpoint: GET /api/classrooms/select
     *
     * Diferencias con index():
     * - Sin paginación: Retorna todas las áreas en una sola petición
     * - Datos mínimos: Solo id y nombre (optimizado para selects)
     * - Ordenamiento fijo: Por nombre ascendente
     * - Soporta ?classroom_name=... para búsqueda en tiempo real
     *
     * Nota: Sin metadatos de paginación porque retorna todo el conjunto.
     *
     * @param Request $request Petición HTTP (query params opcionales)
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con todas las áreas activas
     */
    public function select(Request $request)
    {
        // Obtiene todas las áreas activas sin paginación
        $response = $this->classroomService->getAllForSelect();

        // Manejo de errores del servicio
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa sin paginación
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Obtiene el detalle completo de un ambiente específico.
     *
     * Endpoint: GET /api/classrooms/{id}
     *
     * @param int $id ID del ambiente (entero)
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con detalle del ambiente
     */
    public function show(int $id)
    {
        // Delega la búsqueda al servicio con eager loading
        $response = $this->classroomService->getById($id);

        // Manejo de errores (no encontrado, permisos insuficientes)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con datos completos del ambiente
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea un nuevo ambiente en el sistema.
     *
     * Endpoint: POST /api/classrooms
     *
     * Datos requeridos:
     * - name (string, único)
     * - description (string, opcional)
     *
     * @param StoreClassroomRequest $request Request validado con datos del ambiente
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el ambiente creado
     */
    public function store(StoreClassroomRequest $request)
    {
        // Delega la creación al servicio con datos validados
        $response = $this->classroomService->create($request->validated());

        // Manejo de errores de negocio (duplicados, validaciones)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con el ambiente creado (código 201)
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza los datos de un ambiente existente.
     *
     * Endpoint: PUT /api/classrooms/{id}
     *
     * Permite actualización parcial (solo campos enviados).
     *
     * @param UpdateClassroomRequest $request Request validado con datos a actualizar
     * @param int                    $id      ID del ambiente a actualizar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el ambiente actualizado
     */
    public function update(UpdateClassroomRequest $request, int $id)
    {
        // Delega la actualización al servicio con datos validados
        $response = $this->classroomService->update($request->validated(), $id);

        // Manejo de errores (no encontrado, conflictos de integridad)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con datos actualizados
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina (soft delete) un ambiente del sistema.
     *
     * Endpoint: DELETE /api/classrooms/{id}
     *
     * Nota: En el futuro aquí se validará que no tenga clases programadas.
     *
     * @param int $id ID del ambiente a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON confirmando eliminación
     */
    public function destroy(int $id)
    {
        // Delega la eliminación al servicio (con validaciones de integridad)
        $response = $this->classroomService->delete($id);

        // Manejo de errores (no encontrado, conflictos de integridad)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa (típicamente sin datos)
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
