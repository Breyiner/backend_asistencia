<?php

namespace App\Services\ImportExcel;

use App\Imports\ApprenticesImport;
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
     * Importa aprendices masivamente desde un archivo Excel.
     *
     * Delega el procesamiento fila a fila a ApprenticesImport, que acumula
     * los errores en lugar de lanzar excepciones, permitiendo importaciones parciales.
     * Si hay filas con error, retorna 206 (Partial Content) con el detalle de fallos.
     *
     * @param  array  $data  Debe contener el campo 'file' con el archivo subido.
     * @return array
     */
    public function importApprentices($data)
    {
        // Instancia el import antes de ejecutarlo para poder leer los errores después.
        $import = new ApprenticesImport();

        // Procesa el archivo; ApprenticesImport maneja cada fila internamente.
        Excel::import($import, $data['file']);

        // Si hubo filas con error, retorna 206 (importación parcial) con el detalle.
        // 206 indica que la operación se completó pero no todas las filas fueron procesadas.
        if (!empty($import->errors)) {
            return [
                'error' => true,
                'code' => 206,
                'message' => count($import->errors) . ' fallaron',
                'errors' => $import->errors // Array con el detalle de cada fila fallida.
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Aprendices importados'
        ];
    }
}