<?php

namespace App\Http\Controllers\API\Attendance;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\MonthlyAttendanceRegisterRequest;
use App\Services\Attendance\MonthlyAttendanceRegisterService;

/**
 * Controlador especializado para consulta de registros mensuales de asistencia.
 *
 * Este controlador proporciona un endpoint de solo lectura para visualizar
 * el registro mensual de asistencias de una ficha en formato JSON. Es complementario
 * al método exportMonthlyRegister() de AttendanceController que genera el Excel.
 *
 * Diferencias entre este controlador y AttendanceController::exportMonthlyRegister():
 * - Este retorna JSON para visualización en frontend
 * - exportMonthlyRegister() retorna archivo Excel descargable
 * - Ambos usan el mismo servicio (MonthlyAttendanceRegisterService)
 * - Mismo request de validación (MonthlyAttendanceRegisterRequest)
 *
 * Casos de uso:
 * - Vista previa del registro mensual antes de exportar
 * - Dashboard de instructor con estadísticas mensuales
 * - Reportes interactivos en el frontend
 * - API para integraciones externas
 *
 * Endpoint disponible:
 * - GET /api/monthly-attendance-register - Consultar registro mensual
 *
 * Permisos requeridos:
 * - attendances.view
 * - attendances.report (para acceso a reportes)
 *
 * Estructura de datos retornada:
 * - ficha: Información de la ficha consultada
 * - period: Año y mes del registro
 * - apprentices: Array de aprendices con sus asistencias diarias
 * - summary: Estadísticas agregadas del período
 * - calendar: Matriz de días del mes con estados
 */
class MonthlyAttendanceRegisterController extends Controller
{
    /**
     * Servicio especializado para generación de registros mensuales.
     *
     * Genera datos estructurados con:
     * - Información de la ficha y período
     * - Listado de aprendices con asistencias diarias
     * - Resumen estadístico por aprendiz y global
     * - Calendario con días laborables y festivos
     *
     * @var MonthlyAttendanceRegisterService
     */
    protected $service;

    /**
     * Inyección de dependencias del servicio de registros mensuales.
     *
     * @param MonthlyAttendanceRegisterService $service Servicio de registros mensuales
     */
    public function __construct(MonthlyAttendanceRegisterService $service)
    {
        $this->service = $service;
    }

    /**
     * Consulta el registro mensual de asistencias de una ficha.
     *
     * Endpoint: GET /api/monthly-attendance-register
     *
     * Query parameters (validados en MonthlyAttendanceRegisterRequest):
     * - ficha_id: ID de la ficha (requerido)
     * - year: Año del registro (requerido, formato: YYYY)
     * - month: Mes del registro (requerido, 1-12)
     *
     * Lógica del servicio:
     * - Obtiene todas las clases de la ficha en el mes especificado
     * - Carga asistencias de todos los aprendices para cada clase
     * - Agrupa por día del mes (puede haber múltiples clases por día)
     * - Calcula estadísticas individuales por aprendiz
     * - Genera resumen global del período
     * - Identifica días festivos y no laborables
     *
     *
     * Nota de rendimiento:
     * - Este endpoint puede ser costoso con fichas grandes (100+ aprendices)
     *
     * @param MonthlyAttendanceRegisterRequest $request Request validado con parámetros
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con registro mensual
     */
    public function show(MonthlyAttendanceRegisterRequest $request)
    {
        // Genera datos del registro mensual (misma lógica que el export)
        $response = $this->service->monthlyRegister($request->validated());

        // Manejo de errores con soporte para múltiples tipos de error
        if (!empty($response['error'])) {
            return ResponseFormatter::error(
                $response['message'] ?? 'Error',
                $response['code'] ?? 400,
                $response['errors'] ?? [],
                $response['errorKey'] ?? null
            );
        }

        // Respuesta exitosa con datos estructurados, paginación y resumen
        return ResponseFormatter::success(
            $response['message'] ?? 'Operación exitosa',
            $response['code'] ?? 200,
            $response['data'] ?? [],
            $response['paginate'] ?? [],
            $response['summary'] ?? []
        );
    }
}
