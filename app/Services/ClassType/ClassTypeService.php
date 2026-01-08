<?php

namespace App\Services\ClassType;

use App\Models\ClassType;

class ClassTypeService
{
    public function getAll()
    {
        $classTypes = ClassType::all();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipos de clase obtenidos correctamente',
            'data' => $classTypes
        ];
    }

    public function getById($id)
    {
        $classType = ClassType::find($id);

        if (!$classType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de clase no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de clase obtenido correctamente',
            'data' => $classType
        ];
    }

    public function create($data)
    {
        ClassType::create($data);

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Tipo de clase creado correctamente',
        ];
    }

    public function update($data, $id)
    {
        $classType = ClassType::find($id);

        if (!$classType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de clase no encontrado',
            ];
        }

        $classTypeData = [];

        if (array_key_exists('name', $data)) $classTypeData['name'] = $data['name'];
        if (array_key_exists('description', $data)) $classTypeData['description'] = $data['description'];

        if (empty($classTypeData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $classType->update($classTypeData);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de clase actualizado correctamente',
        ];
    }

    public function delete($id)
    {
        $classType = ClassType::find($id);

        if (!$classType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de clase no encontrado',
            ];
        }

        $classType->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de clase eliminado correctamente',
        ];
    }
}
