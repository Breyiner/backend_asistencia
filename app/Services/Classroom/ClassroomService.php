<?php

namespace App\Services\Classroom;

use App\Events\ResourceChanged;
use App\Models\Classroom;
use Illuminate\Support\Facades\Auth;

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
        $classroom = Classroom::create($data);

        event(new ResourceChanged(
            'crear',
            Classroom::class,
            $classroom->id,
            Auth::id(),
            'Ambiente'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Ambiente creado correctamente',
            'data' => $classroom,
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

        event(new ResourceChanged(
            'actualizar',
            Classroom::class,
            $classroom->id,
            Auth::id(),
            'Ambiente'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente actualizado correctamente',
            'data' => $classroom->fresh(),
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

        event(new ResourceChanged(
            'eliminar',
            Classroom::class,
            $id,
            Auth::id(),
            'Ambiente'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente eliminado correctamente',
        ];
    }
}