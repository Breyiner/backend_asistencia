<?php

namespace App\Services\ImportExcel;

use App\Imports\ApprenticesImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportExcelService
{
    
    public function importApprentices($data) {

        $import = new ApprenticesImport();

        Excel::import($import, $data['file']);

        if (!empty($import->errors)) {
                return [
                    'error' => true,
                    'code' => 206,
                    'message' => count($import->errors) . ' fallaron',
                    'errors' => $import->errors
                ];
            }


        return [
                'error' => false,
                'code' => 200,
                'message' => 'Aprendices importados'
            ];

    }

}
