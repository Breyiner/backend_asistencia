<?php

namespace App\Services\ImportExcel;

use App\Imports\ApprenticesImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Servicio para la importación masiva de datos desde archivos Excel.
 *
 * Usa el paquete Maatwebsite/Excel para procesar el archivo y delega
 * la lógica de importación a clases Import dedicadas por entidad.
 */
class ImportExcelService
{
    /**
     * Clave de cache para errores de importación de aprendices.
     * Se le concatena el ID del usuario autenticado.
     */
    private const ERRORS_CACHE_KEY = 'import_apprentices_errors_';

    /**
     * Minutos que se conservan los errores en cache.
     * Suficiente para que el usuario descargue el Excel de errores.
     */
    private const ERRORS_CACHE_TTL = 10;

    /**
     * Importa aprendices masivamente desde un archivo Excel.
     *
     * Delega el procesamiento fila a fila a ApprenticesImport, que:
     * 1. Valida TODOS los campos por fila simultáneamente
     * 2. Crea aprendices válidos (Apprentice + Profile + rol APRENDIZ)
     * 3. Agrupa errores MÚLTIPLES por fila en onFailure()
     * 4. Retorna 206 si hay fallos parciales
     *
     * Si hay errores, los guarda en cache 10 minutos para que el
     * usuario pueda descargar el Excel de errores desde el frontend.
     *
     * @param array $data Debe contener 'file' con UploadedFile
     * @return array Array para ResponseFormatter::error/success
     */
    public function importApprentices($data): array
    {
        $import = new ApprenticesImport();

        Excel::import($import, $data['file']);

        $groupedErrors = $import->getGroupedErrors();

        if (!empty($groupedErrors)) {
            // Guarda los errores en cache asociados al usuario actual.
            // Clave única por usuario → cada uno ve solo sus propios errores.
            Cache::put(
                self::ERRORS_CACHE_KEY . Auth::id(),
                $groupedErrors,
                now()->addMinutes(self::ERRORS_CACHE_TTL)
            );

            return [
                'error'    => true,
                'code'     => 206,
                'message'  => count($groupedErrors) . ' fallaron',
                'errors'   => $groupedErrors,
                'errorKey' => 'import_apprentices_failed',
            ];
        }

        // Limpia cache previa si la importación fue exitosa
        Cache::forget(self::ERRORS_CACHE_KEY . Auth::id());

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Aprendices importados',
        ];
    }

    /**
     * Retorna los errores de la última importación fallida del usuario.
     * Usado por el endpoint de descarga del Excel de errores.
     *
     * @return array|null Errores agrupados por fila, o null si no hay cache
     */
    public function getImportErrors(): ?array
    {
        return Cache::get(self::ERRORS_CACHE_KEY . Auth::id());
    }
}