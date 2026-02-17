<?php

namespace App\Services\AttendanceStatus;

use App\Events\ResourceChanged;
use App\Models\AttendanceStatus;
use Illuminate\Support\Facades\Auth;

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
        $status = AttendanceStatus::create($data);

        event(new ResourceChanged(
            'crear',
            AttendanceStatus::class,
            $status->id,
            Auth::id(),
            'Estado de asistencia'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Estado de asistencia creado correctamente',
            'data' => $status,
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

        event(new ResourceChanged(
            'actualizar',
            AttendanceStatus::class,
            $status->id,
            Auth::id(),
            'Estado de asistencia'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia actualizado correctamente',
            'data' => $status->fresh(),
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

        event(new ResourceChanged(
            'eliminar',
            AttendanceStatus::class,
            $id,
            Auth::id(),
            'Estado de asistencia'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia eliminado correctamente',
        ];
    }
}