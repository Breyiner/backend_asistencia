<?php

namespace App\Http\Controllers\API\Attendance;

use App\Exports\MonthlyRegisterExport;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\MonthlyAttendanceRegisterRequest;
use App\Http\Requests\Attendance\ScanAttendanceRequest;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\MonthlyAttendanceRegisterService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

/**
 * Controlador REST para la gestión de asistencias de aprendices.
 *
 * Este es el controlador principal del sistema de asistencias. Gestiona
 * el registro, consulta, actualización y eliminación de asistencias,
 * además de funcionalidades especiales como escaneo QR/NFC y exportación
 * de registros mensuales.
 *
 * Flujo de registro de asistencia:
 * 1. Instructor crea clase (ClassRealController)
 * 2. Se generan registros de asistencia para todos los aprendices de la ficha
 * 3. Aprendices registran entrada/salida mediante scan() o actualización manual
 * 4. Sistema calcula automáticamente: estado, minutos tarde, horas efectivas
 * 5. Instructor puede ajustar manualmente con justificaciones
 *
 * Tipos de registro:
 * - Automático: Via escaneo QR/NFC con dispositivo móvil (scan)
 * - Manual: Instructor registra entrada/salida desde dashboard (store/update)
 * - Importado: Carga masiva desde Excel (no implementado en este controlador)
 *
 * Estados de asistencia:
 * - PRESENTE: Asistió completo
 * - TARDE: Asistió pero llegó tarde
 * - AUSENTE: No asistió sin justificación
 * - JUSTIFICADO: Falta con justificación válida
 * - PENDIENTE: Aún no ha registrado entrada
 *
 * Endpoints disponibles:
 * - GET    /api/attendances                        - Listado general
 * - GET    /api/attendances/{id}                   - Detalle de asistencia
 * - GET    /api/attendances/class/{realClassId}    - Asistencias de una clase
 * - POST   /api/attendances                        - Crear asistencia manual
 * - POST   /api/attendances/scan                   - Registrar via escaneo
 * - PUT    /api/attendances/{id}                   - Actualizar asistencia
 * - DELETE /api/attendances/{id}                   - Eliminar asistencia
 * - POST   /api/attendances/export-monthly         - Exportar registro mensual
 *
 * Permisos requeridos:
 * - attendances.view
 * - attendances.create
 * - attendances.update
 * - attendances.delete
 * - attendances.scan (aprendices)
 * - attendances.export
 *
 * Broadcasting:
 * - Emite eventos en tiempo real via Laravel Reverb cuando se registra asistencia
 * - Canal: attendance.{class_real_id}
 * - Permite actualización instantánea del dashboard del instructor
 */
class AttendanceController extends Controller
{
    /**
     * Servicio de lógica de negocio para asistencias.
     *
     * Maneja:
     * - Cálculo de estados y tiempos
     * - Validaciones de horarios y permisos
     * - Broadcasting de eventos en tiempo real
     * - Integración con sistema de notificaciones
     *
     * @var AttendanceService
     */
    protected $attendanceService;

    /**
     * Inyección de dependencias del servicio de asistencias.
     *
     * @param AttendanceService $attendanceService Servicio de asistencias
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Lista todas las asistencias del sistema.
     *
     * Endpoint: GET /api/attendances
     *

     *
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con listado de asistencias
     */
    public function index()
    {
        // Obtiene todas las asistencias con relaciones cargadas
        $response = $this->attendanceService->getAll();

        // Manejo de errores del servicio
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con datos
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Crea un registro de asistencia manualmente.
     *
     * Endpoint: POST /api/attendances
     *
     *
     * @param StoreAttendanceRequest $request Request validado con datos de asistencia
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con asistencia creada
     */
    public function store(StoreAttendanceRequest $request)
    {
        // Delega la creación al servicio con datos validados
        $response = $this->attendanceService->create($request->validated());

        // Manejo de errores (duplicados, validaciones de negocio)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con asistencia creada
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Exporta el registro mensual de asistencias de una ficha a Excel.
     *
     * Endpoint: POST /api/attendances/export-monthly
     *
     * Genera un archivo Excel detallado con:
     * - Información de la ficha (nombre, código, programa, jornada)
     * - Listado de aprendices con totales de asistencia
     * - Calendario del mes con estados por día
     *
     * @param MonthlyAttendanceRegisterRequest $request Request validado con parámetros
     * @param MonthlyAttendanceRegisterService $service Servicio inyectado para generar datos
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function exportMonthlyRegister(MonthlyAttendanceRegisterRequest $request, MonthlyAttendanceRegisterService $service)
    {
        // Obtiene datos validados (ficha_id, year, month)
        $data = $request->validated();

        // Genera datos estructurados para el Excel
        $result = $service->monthlyRegister($data);

        // Manejo de errores (ficha no encontrada, sin datos, etc.)
        if (!empty($result['error'])) {
            return response()->json($result, $result['code'] ?? 400);
        }

        // Extrae payload con datos para el export
        $payload = $result['data'];

        // Construye nombre de archivo descriptivo
        $fileName = "registro_mensual_{$payload['period']['year']}_{$payload['period']['month']}_ficha_{$payload['ficha']['id']}.xlsx";

        // Genera y descarga Excel usando Maatwebsite/Laravel-Excel
        return Excel::download(new MonthlyRegisterExport($payload), $fileName);
    }

    /**
     * Obtiene el detalle completo de una asistencia específica.
     *
     * Endpoint: GET /api/attendances/{id}
     *
     *
     * @param int $id ID de la asistencia
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con detalle de asistencia
     */
    public function show(int $id)
    {
        // Delega la búsqueda al servicio con eager loading
        $response = $this->attendanceService->getById($id);

        // Manejo de errores (no encontrado, permisos insuficientes)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con datos completos
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Obtiene todas las asistencias de una clase específica con resumen estadístico.
     *
     * Endpoint: GET /api/attendances/class/{realClassId}
     *
     *
     * NOTA: Este endpoint no implementa paginación porque siempre retorna
     * un conjunto acotado (aprendices de una ficha en una clase específica).
     *
     * @param int $realClassId ID de la clase real (class_reals.id)
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con asistencias y resumen
     */
    public function byClassRealId(int $realClassId)
    {
        // Obtiene asistencias de la clase con resumen estadístico
        $response = $this->attendanceService->byClassRealId($realClassId);

        // Manejo de errores (clase no encontrada, sin permisos)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con datos, paginación (si aplica) y resumen
        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? [],
            $response['summary']
        );
    }

    /**
     * Actualiza una asistencia existente.
     *
     * Endpoint: PUT /api/attendances/{id}
     *
     *
     * @param UpdateAttendanceRequest $request Request validado con datos a actualizar
     * @param int                     $id      ID de la asistencia a actualizar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con asistencia actualizada
     */
    public function update(UpdateAttendanceRequest $request, $id)
    {
        // Delega la actualización al servicio con todos los datos del request
        $result = $this->attendanceService->update($request->all(), $id);

        // Manejo de errores con soporte para errores de validación detallados
        if (!empty($result['error'])) {
            return ResponseFormatter::error(
                $result['message'] ?? 'Error',
                $result['code'] ?? 400,
                $result['errors'] ?? [],
                $result['errorKey'] ?? null
            );
        }

        // Respuesta exitosa con datos actualizados, paginación y resumen
        return ResponseFormatter::success(
            $result['message'] ?? 'Operación exitosa',
            $result['code'] ?? 200,
            $result['data'] ?? null,
            $result['paginate'] ?? [],
            $result['summary'] ?? []
        );
    }

    /**
     * Registra asistencia mediante escaneo de código de barras.
     *
     * Endpoint: POST /api/attendances/scan
     *
     * @param ScanAttendanceRequest $request Request validado con código de barras
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con resultado del escaneo
     */
    public function scan(ScanAttendanceRequest $request)
    {
        // Delega el escaneo al servicio con validación de de barras
        $response = $this->attendanceService->scanCheckIn(
            $request->validated()
        );

        // Manejo de errores (código inválido, expirado, duplicado, etc.)
        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        // Respuesta exitosa con datos de la asistencia registrada
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina (soft delete) una asistencia del sistema.
     *
     * Endpoint: DELETE /api/attendances/{id}
     *
     *
     * @param int $id ID de la asistencia a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON confirmando eliminación
     */
    public function destroy(int $id)
    {
        // Delega la eliminación al servicio (con validaciones de permisos)
        $response = $this->attendanceService->delete($id);

        // Manejo de errores (no encontrado, permisos insuficientes)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa (típicamente sin datos)
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
