<?php

namespace App\Services\AttendanceStatus;

use App\Models\AttendanceStatus;

class AttendanceStatusService
{
    public function getAll()
    {
        $statuses = AttendanceStatus::all();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estados de asistencia obtenidos correctamente',
            'data' => $statuses
        ];
    }

    public function getById($id)
    {
        $status = AttendanceStatus::find($id);

        if (!$status) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Estado de asistencia no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia obtenido correctamente',
            'data' => $status
        ];
    }

    public function create($data)
    {
        AttendanceStatus::create($data);

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Estado de asistencia creado correctamente',
        ];
    }

    public function update($data, $id)
    {
        $status = AttendanceStatus::find($id);

        if (!$status) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Estado de asistencia no encontrado',
            ];
        }

        $statusData = [];

        if (array_key_exists('name', $data)) $statusData['name'] = $data['name'];
        if (array_key_exists('description', $data)) $statusData['description'] = $data['description'];

        if (empty($statusData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $status->update($statusData);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia actualizado correctamente',
        ];
    }

    public function delete($id)
    {
        $status = AttendanceStatus::find($id);

        if (!$status) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Estado de asistencia no encontrado',
            ];
        }

        $status->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia eliminado correctamente',
        ];
    }
}
