<?php

namespace App\Http\Controllers\API\Day;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Day\StoreDayRequest;
use App\Http\Requests\Day\UpdateDayRequest;
use App\Services\Day\DayService;
use Illuminate\Http\Request;

/**
 * Controlador REST para la gestión de días de la semana.
 *
 * Administra el catálogo de días (Lunes–Domingo) y su configuración
 * para programación de horarios y validaciones de clases.
 */
class DayController extends Controller
{
    /**
     * Servicio de lógica de negocio para días.
     *
     * @var DayService
     */
    protected $dayService;

    /**
     * Inyección de dependencias del servicio de días.
     *
     * @param DayService $dayService
     */
    public function __construct(DayService $dayService)
    {
        // Guarda el servicio para usarlo en los métodos del controlador.
        $this->dayService = $dayService;
    }

    /**
     * Lista todos los días configurados.
     *
     * GET /api/days
     *
     * Retorna el catálogo de días (normalmente 7) sin paginación.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Obtiene todos los días desde el servicio.
        $response = $this->dayService->getAll();

        // Si hay error, devuelve respuesta estándar de error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con el listado de días.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea un nuevo día (normalmente solo para configuraciones personalizadas).
     *
     * POST /api/days
     *
     * @param  StoreDayRequest  $request  Datos validados del día.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDayRequest $request)
    {
        // Crea el día con los datos ya validados.
        $response = $this->dayService->create($request->validated());

        // Devuelve error si el servicio lo indica.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve el día creado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Muestra el detalle de un día específico.
     *
     * GET /api/days/{id}
     *
     * @param  mixed  $id  ID del día.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Obtiene el día por ID desde el servicio.
        $response = $this->dayService->getById($id);

        // Si el día no existe o hay error, responde con error estándar.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve la información del día.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza la configuración de un día.
     *
     * PUT /api/days/{id}
     *
     * @param  UpdateDayRequest  $request  Datos validados a actualizar.
     * @param  mixed             $id       ID del día.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDayRequest $request, $id)
    {
        // Actualiza el día con los datos proporcionados.
        $response = $this->dayService->update($request->validated(), $id);

        // Si el servicio devuelve error (validación, integridad), responde con error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve el día actualizado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina un día (solo días personalizados, no los estándar).
     *
     * DELETE /api/days/{id}
     *
     * @param  mixed  $id  ID del día.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Solicita al servicio eliminar el día indicado.
        $response = $this->dayService->delete($id);

        // Si no puede eliminarse (asociaciones, día estándar, etc.), responde con error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devuelve confirmación de eliminación.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
