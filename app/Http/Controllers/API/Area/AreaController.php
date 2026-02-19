<?php

namespace App\Http\Controllers\API\Area;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Services\Area\AreaService;
use Illuminate\Http\Request;

/**
 * Controlador REST para la gestión de áreas de formación.
 *
 * Las áreas representan las diferentes unidades organizativas o departamentos
 * dentro de la institución (ej: Sistemas, Administración, Salud, etc.).
 * Cada área agrupa múltiples programas de formación y fichas.
 *
 * Funcionalidades principales:
 * - CRUD completo de áreas
 * - Endpoint optimizado para dropdowns/select (sin paginación)
 * - Validación de datos mediante Form Requests
 * - Respuestas estandarizadas con ResponseFormatter
 *
 * Relaciones del modelo Area:
 * - hasMany programs: Programas de formación asociados
 * - hasMany fichas: Fichas asignadas al área
 * - hasMany users: Usuarios que gestionan el área (opcional)
 *
 * Endpoints disponibles:
 * - GET    /api/areas          - Listado paginado de áreas
 * - GET    /api/areas/select   - Listado completo para selects (sin paginación)
 * - GET    /api/areas/{id}     - Detalle de un área específica
 * - POST   /api/areas          - Crear nueva área
 * - PUT    /api/areas/{id}     - Actualizar área existente
 * - DELETE /api/areas/{id}     - Eliminar área (soft delete)
 *
 * Permisos requeridos (configurados en routes/api.php):
 * - areas.view
 * - areas.create
 * - areas.update
 * - areas.delete
 *
 * Uso típico:
 * - Administradores crean y gestionan áreas
 * - Gestores de fichas seleccionan áreas al crear programas
 * - Instructores visualizan áreas para filtrar información
 */
class AreaController extends Controller
{
    /**
     * Servicio de lógica de negocio para áreas.
     *
     * Maneja operaciones CRUD, validaciones de negocio y consultas complejas.
     *
     * @var AreaService
     */
    protected $areaService;

    /**
     * Inyección de dependencias del servicio de áreas.
     *
     * Laravel resuelve automáticamente la instancia de AreaService
     * desde el contenedor de servicios.
     *
     * @param AreaService $areaService Servicio de áreas
     */
    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }

    /**
     * Lista todas las áreas con paginación.
     *
     * Endpoint: GET /api/areas
     *
     *
     * @param Request $request Petición HTTP con query parameters
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada con paginación
     */
    public function index(Request $request)
    {
        // Obtiene per_page del query string, default 10
        $response = $this->areaService->getAll($request->get('per_page', 10));

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
     * Lista todas las áreas optimizado para componentes select/dropdown.
     *
     * Endpoint: GET /api/areas/select
     *
     * Diferencias con index():
     * - Sin paginación: Retorna todas las áreas en una sola petición
     * - Datos mínimos: Solo id, nombre y código (optimizado para selects)
     * - Ordenamiento fijo: Por nombre ascendente
     *
     * Nota: Sin metadatos de paginación porque retorna todo el conjunto.
     *
     * @param Request $request Petición HTTP (query params no utilizados)
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con todas las áreas activas
     */
    public function select(Request $request)
    {
        // Obtiene todas las áreas activas sin paginación
        $response = $this->areaService->getAllForSelect();

        // Manejo de errores del servicio
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa sin paginación
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Obtiene el detalle completo de un área específica.
     *
     * Endpoint: GET /api/areas/{id}
     *
     *
     * @param string $id ID del área (UUID o entero según modelo)
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con detalle del área
     */
    public function show(string $id)
    {
        // Delega la búsqueda al servicio con eager loading
        $response = $this->areaService->getById($id);

        // Manejo de errores (no encontrado, permisos insuficientes)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con datos completos del área
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea una nueva área en el sistema.
     *
     * Endpoint: POST /api/areas
     *
     * @param StoreAreaRequest $request Request validado con datos del área
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el área creada
     */
    public function store(StoreAreaRequest $request)
    {
        // Delega la creación al servicio con datos validados
        $response = $this->areaService->create($request->validated());

        // Manejo de errores de negocio (duplicados, validaciones)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con el área creada
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza los datos de un área existente.
     *
     * Endpoint: PUT /api/areas/{id}
     *
     *
     * @param UpdateAreaRequest $request Request validado con datos a actualizar
     * @param string            $id      ID del área a actualizar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el área actualizada
     */
    public function update(UpdateAreaRequest $request, string $id)
    {
        // Delega la actualización al servicio con datos validados
        $response = $this->areaService->update($request->validated(), $id);

        // Manejo de errores (no encontrado, conflictos de integridad)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con datos actualizados
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina (soft delete) un área del sistema.
     *
     * Endpoint: DELETE /api/areas/{id}
     *
     *
     * @param string $id ID del área a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON confirmando eliminación
     */
    public function destroy(string $id)
    {
        // Delega la eliminación al servicio (con validaciones de integridad)
        $response = $this->areaService->delete($id);

        // Manejo de errores (no encontrado, conflictos de integridad)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa (típicamente sin datos)
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
