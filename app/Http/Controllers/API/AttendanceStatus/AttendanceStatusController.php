<?php

namespace App\Http\Controllers\API\AttendanceStatus;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceStatus\StoreAttendanceStatusRequest;
use App\Http\Requests\AttendanceStatus\UpdateAttendanceStatusRequest;
use App\Services\AttendanceStatus\AttendanceStatusService;

/**
 * Controlador REST para la gestión de estados de asistencia.
 *
 * Expone operaciones CRUD sobre el catálogo de estados usados
 * para marcar las asistencias (por ejemplo: PRESENTE, TARDE, AUSENTE).
 */
class AttendanceStatusController extends Controller
{
    /**
     * Servicio de dominio para estados de asistencia.
     *
     * Encapsula la lógica de negocio (validaciones, reglas, consultas).
     *
     * @var AttendanceStatusService
     */
    protected $attendanceStatusService;

    /**
     * Constructor: inyecta el servicio de estados de asistencia.
     *
     * @param AttendanceStatusService $attendanceStatusService
     */
    public function __construct(AttendanceStatusService $attendanceStatusService)
    {
        // Guardamos la instancia del servicio para usarla en los métodos del controlador.
        $this->attendanceStatusService = $attendanceStatusService;
    }

    /**
     * Devuelve el listado completo de estados de asistencia.
     *
     * Método HTTP: GET
     * Ruta: /api/attendance-statuses
     *
     * No aplica paginación porque es un catálogo pequeño.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Pedimos al servicio todos los registros de estados.
        $response = $this->attendanceStatusService->getAll();

        // Si el servicio indica error, construimos respuesta de error uniforme.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // En caso de éxito, devolvemos los datos en el formato estándar.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea un nuevo estado de asistencia.
     *
     * Método HTTP: POST
     * Ruta: /api/attendance-statuses
     *
     * Los datos llegan validados por StoreAttendanceStatusRequest.
     *
     * @param  StoreAttendanceStatusRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAttendanceStatusRequest $request)
    {
        // Obtenemos únicamente los campos validados del request.
        $data = $request->validated();

        // Delegamos en el servicio la creación del registro.
        $response = $this->attendanceStatusService->create($data);

        // Si hubo algún problema (validación de negocio, duplicados, etc.).
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Devolvemos el estado recién creado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Muestra un estado de asistencia específico por su ID.
     *
     * Método HTTP: GET
     * Ruta: /api/attendance-statuses/{id}
     *
     * @param  int  $id  Identificador del estado de asistencia.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        // Solicitamos al servicio el estado correspondiente al ID.
        $response = $this->attendanceStatusService->getById($id);

        // Si no se encuentra o hay error, se retorna respuesta de error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Si todo va bien, devolvemos la información del estado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza los datos de un estado de asistencia existente.
     *
     * Método HTTP: PUT
     * Ruta: /api/attendance-statuses/{id}
     *
     * El FormRequest UpdateAttendanceStatusRequest valida los campos actualizables.
     *
     * @param  UpdateAttendanceStatusRequest  $request
     * @param  int                            $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAttendanceStatusRequest $request, int $id)
    {
        // Tomamos los datos ya validados.
        $data = $request->validated();

        // Pasamos los datos y el ID al servicio para aplicar la actualización.
        $response = $this->attendanceStatusService->update($data, $id);

        // Ante cualquier error de negocio o validación extra, devolvemos error.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // En caso de éxito, retornamos el estado actualizado.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina un estado de asistencia.
     *
     * Método HTTP: DELETE
     * Ruta: /api/attendance-statuses/{id}
     *
     * El servicio normalmente valida si el estado puede eliminarse (dependencias, uso, etc.).
     *
     * @param  int  $id  ID del estado a eliminar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        // Solicitamos al servicio que elimine el registro indicado.
        $response = $this->attendanceStatusService->delete($id);

        // Si la eliminación no está permitida o falla, se retorna un error estándar.
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Si se elimina correctamente, devolvemos confirmación.
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
