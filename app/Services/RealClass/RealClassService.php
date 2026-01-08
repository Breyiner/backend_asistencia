<?php

namespace App\Services\RealClass;

use App\Models\RealClass;

class RealClassService
{
    public function getAll()
    {
        $realClasses = RealClass::with([
                'instructor',
                'classType', 
                'classroom', 
                'shift', 
                'scheduleSession'
            ])
            ->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clases reales obtenidas correctamente',
            'data' => $realClasses
        ];
    }

    public function getById($id)
    {
        $realClass = RealClass::with([
                'instructor', 
                'classType', 
                'classroom', 
                'shift', 
                'scheduleSession'
            ])->find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real obtenida correctamente',
            'data' => $realClass
        ];
    }

    public function create($data)
    {

        $data['execution_date'] = now()->format('Y-m-d');

        RealClass::create($data);

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Clase real registrada'
        ];
    }

    public function update($data, $id)
    {
        $realClass = RealClass::find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
            ];
        }

        $realClassData = [];

        if (array_key_exists('instructor_id', $data)) $realClassData['instructor_id'] = $data['instructor_id'];
        if (array_key_exists('class_type_id', $data)) $realClassData['class_type_id'] = $data['class_type_id'];
        if (array_key_exists('classroom_id', $data)) $realClassData['classroom_id'] = $data['classroom_id'];
        if (array_key_exists('shift_id', $data)) $realClassData['shift_id'] = $data['shift_id'];
        if (array_key_exists('schedule_session_id', $data)) $realClassData['schedule_session_id'] = $data['schedule_session_id'];
        if (array_key_exists('execution_date', $data)) $realClassData['execution_date'] = $data['execution_date'];
        if (array_key_exists('start_hour', $data)) $realClassData['start_hour'] = $data['start_hour'];
        if (array_key_exists('end_hour', $data)) $realClassData['end_hour'] = $data['end_hour'];
        if (array_key_exists('original_date', $data)) $realClassData['original_date'] = $data['original_date'];
        if (array_key_exists('observations', $data)) $realClassData['observations'] = $data['observations'];

        if (empty($realClassData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $realClass->update($realClassData);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real actualizada correctamente',
        ];
    }

    public function delete($id)
    {
        $realClass = RealClass::find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
            ];
        }

        $realClass->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real eliminada correctamente',
        ];
    }
}
