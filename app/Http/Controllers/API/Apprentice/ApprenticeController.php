<?php

namespace App\Http\Controllers\API\Apprentice;

use App\Exports\ImportErrorsExport;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Apprentice\StoreApprenticeRequest;
use App\Http\Requests\Apprentice\UpdateApprenticeRequest;
use App\Http\Requests\ImportApprentice\ImportApprenticeRequest;
use App\Services\Apprentice\ApprenticeService;
use App\Services\ImportExcel\ImportExcelService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controlador REST para la gestión de aprendices.
 *
 * Proporciona endpoints para el CRUD completo de aprendices y la importación
 * masiva desde archivos Excel. Este controlador delega la lógica de negocio
 * a servicios especializados para mantener separación de responsabilidades.
 *
 * Arquitectura:
 * - Controller: Validación de requests y formateo de respuestas
 * - Service: Lógica de negocio y orquestación
 *
 * Servicios utilizados:
 * - ApprenticeService: CRUD y lógica de aprendices
 * - ImportExcelService: Importación masiva desde Excel
 *
 * Respuestas estandarizadas:
 * - Éxito: ResponseFormatter::success()
 * - Error: ResponseFormatter::error()
 *
 * Endpoints disponibles:
 * - GET    /api/apprentices          - Listado paginado
 * - GET    /api/apprentices/{id}     - Detalle de un aprendice
 * - POST   /api/apprentices          - Crear aprendice
 * - PUT    /api/apprentices/{id}     - Actualizar aprendice
 * - DELETE /api/apprentices/{id}     - Eliminar aprendice
 * - POST   /api/apprentices/import   - Importación masiva
 * - GET    /api/apprentices/template - Descargar plantilla Excel
 *
 * Permisos requeridos (definidos en routes/api.php):
 * - apprentices.view
 * - apprentices.create
 * - apprentices.update
 * - apprentices.delete
 * - apprentices.import
 */
class ApprenticeController extends Controller
{
    /**
     * Servicio de lógica de negocio para aprendices.
     *
     * @var ApprenticeService
     */
    protected $apprenticeService;

    /**
     * Servicio de importación desde archivos Excel.
     *
     * @var ImportExcelService
     */
    protected $importService;

    /**
     * Inyección de dependencias de servicios.
     *
     * Laravel resuelve automáticamente las dependencias del constructor.
     *
     * @param ApprenticeService   $apprenticeService   Servicio de aprendices
     * @param ImportExcelService  $importExcelService  Servicio de importación
     */
    public function __construct(ApprenticeService $apprenticeService, ImportExcelService $importExcelService)
    {
        $this->apprenticeService = $apprenticeService;
        $this->importService = $importExcelService;
    }

    /**
     * Lista todos los aprendices con paginación.
     *
     * Endpoint: GET /api/apprentices
     *
     * Query parameters:
     * - per_page: Número de registros por página (default: 10)
     * - page: Número de página actual (manejado por Laravel automáticamente)
     *
     * @param Request $request Petición HTTP con query parameters
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada
     */
    public function index(Request $request)
    {
        // Obtiene per_page del query string, default 10
        $response = $this->apprenticeService->getAll($request->get('per_page', 10));

        // Si el servicio retorna error, formatea respuesta de error
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con datos y metadatos de paginación
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? [], $response['paginate']);
    }

    /**
     * Obtiene el detalle de un aprendice específico.
     *
     * Endpoint: GET /api/apprentices/{id}
     *
     *
     * @param string $id ID del aprendiz (UUID o entero según modelo)
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada
     */
    public function show(string $id)
    {
        // Delega la búsqueda al servicio
        $response = $this->apprenticeService->getById($id);

        // Manejo de errores (aprendiz no encontrado, no autorizado, etc.)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con datos del aprendiz
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Descarga la plantilla Excel para importación masiva de aprendices.
     *
     * Endpoint: GET /api/apprentices/template
     *
     * La plantilla incluye:
     * - Columnas requeridas: documento, nombre, apellidos, email, ficha_code
     * - Columnas opcionales: telefono, direccion, fecha_nacimiento
     * - Hoja de instrucciones con validaciones y ejemplos
     *
     * Ubicación del archivo:
     * storage/app/templates/plantilla_aprendices.xlsx
     *
     * Headers de respuesta:
     * - Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
     * - Content-Disposition: attachment; filename="plantilla_importacion_aprendices.xlsx"
     *
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadTemplate()
    {
        // Ruta absoluta del archivo de plantilla
        $path = storage_path('app/templates/plantilla_aprendices.xlsx');

        // Valida que el archivo exista antes de descargar
        if (!file_exists($path)) {
            return ResponseFormatter::error('Plantilla no encontrada', 404);
        }

        // Descarga el archivo con nombre personalizado
        return response()->download(
            $path,
            'plantilla_importacion_aprendices.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]
        );
    }

    /**
     * Crea un nuevo aprendiz en el sistema.
     *
     * Endpoint: POST /api/apprentices
     *
     * @param StoreApprenticeRequest $request Request validado con datos del aprendiz
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada
     */
    public function store(StoreApprenticeRequest $request)
    {
        // Obtiene datos validados del request
        $data = $request->validated();

        // Delega la creación al servicio
        $response = $this->apprenticeService->create($data);

        // Manejo de errores de negocio (duplicados, validaciones, etc.)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con el aprendiz creado
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Actualiza los datos de un aprendiz existente.
     *
     * Endpoint: PUT /api/apprentices/{id}
     *
     * @param UpdateApprenticeRequest $request Request validado con datos a actualizar
     * @param string                  $id      ID del aprendiz a actualizar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada
     */
    public function update(UpdateApprenticeRequest $request, string $id)
    {
        // Obtiene solo los campos validados (pueden ser parciales)
        $data = $request->validated();

        // Delega la actualización al servicio con ID y datos
        $response = $this->apprenticeService->update($data, $id);

        // Manejo de errores (no encontrado, validaciones, permisos)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa con datos actualizados
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Elimina (soft delete) un aprendiz del sistema.
     *
     * Endpoint: DELETE /api/apprentices/{id}
     *
     *
     * @param string $id ID del aprendiz a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta JSON formateada
     */
    public function destroy(string $id)
    {
        // Delega la eliminación al servicio
        $response = $this->apprenticeService->destroy($id);

        // Manejo de errores (no encontrado, conflictos de integridad)
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        // Respuesta exitosa (típicamente sin datos)
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Importa aprendices masivamente desde un archivo Excel.
     *
     * Endpoint: POST /api/apprentices/import
     *
     * Validaciones (ImportApprenticeRequest):
     * - file: requerido, archivo, extensión xlsx|xls, max:5MB
     *
     * Proceso de importación:
     * 1. Valida estructura del Excel (columnas requeridas)
     * 2. Procesa cada fila con validaciones individuales
     * 3. Crea aprendices válidos y registra errores por fila
     * 4. Retorna resumen: creados, actualizados, errores
     *
     * Validaciones por fila:
     * - documento: único, numérico, requerido
     * - email: único, formato válido, requerido
     * - ficha_code: debe existir una ficha con ese código
     *
     * Manejo de errores:
     * - Errores de formato: Retorna lista de filas con problemas
     * - Errores de validación: Continúa procesando filas válidas
     * - Errores críticos: Detiene la importación
     *
     *
     * @param ImportApprenticeRequest $request Request validado con archivo Excel
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con resultado de importación
     */
    public function import(ImportApprenticeRequest $request)
    {
        // Obtiene datos validados (incluye el archivo subido)
        $data = $request->validated();

        // Delega la importación al servicio especializado
        $response = $this->importService->importApprentices($data);

        // Si hay errores críticos, retorna con detalles de errores por fila
        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code'], $response['errors'], $response['errorKey']);

        // Respuesta exitosa con resumen de la importación
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    /**
     * Descarga Excel con errores de la última importación fallida.
     *
     * GET /api/apprentices/import/errors-excel
     *
     * Lee errores del caché del usuario (10 min TTL) y genera Excel:
     * - Mismo formato/estilos que plantilla_aprendices.xlsx
     * - Columna extra "errores" con fondo rojo y mensajes concatenados
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadErrorsExcel()
    {
        // Lee errores del caché del usuario actual
        $errors = $this->importService->getImportErrors();

        // 404 si no hay errores (expiró caché o import exitosa)
        if (empty($errors)) {
            return ResponseFormatter::error(
                'No hay errores de importación recientes',
                404,
                [],
                'import_errors_not_found'
            );
        }

        // Genera Excel con tu ImportErrorsExport
        return Excel::download(
            new ImportErrorsExport($errors),
            'aprendices_errores_' . now()->format('Y-m-d_H-i') . '.xlsx'
        );
    }
}
