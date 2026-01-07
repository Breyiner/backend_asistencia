<?php

namespace App\Services\Classroom;

use App\Models\Classroom;

class ClassroomService
{
    public function getAll()
    {
        $classrooms = Classroom::orderBy('name')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambientes obtenidos correctamente',
            'data' => $classrooms
        ];
    }

    public function getById(int $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Ambiente no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente obtenido correctamente',
            'data' => $classroom
        ];
    }

    public function create(array $data)
    {
        Classroom::create($data);

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Ambiente creado correctamente',
        ];
    }

    public function update(array $data, int $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Ambiente no encontrado',
            ];
        }

        $classroomData = [];

        if (array_key_exists('name', $data)) {
            $classroomData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $classroomData['description'] = $data['description'];
        }

        if (empty($classroomData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $classroom->update($classroomData);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente actualizado correctamente',
        ];
    }

    public function delete(int $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Ambiente no encontrado',
            ];
        }

        $classroom->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente eliminado correctamente',
        ];
    }
}
