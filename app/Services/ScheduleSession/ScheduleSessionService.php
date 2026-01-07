<?php

namespace App\Services\ScheduleSession;

use App\Models\ScheduleSession;

class ScheduleSessionService
{
    public function getAll(): array
    {
        $sessions = ScheduleSession::with(['instructor', 'schedule', 'shift', 'classroom', 'day'])
            ->orderBy('day_id')
            ->orderBy('start_time')
            ->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registros del horario obtenidos correctamente',
            'data' => $sessions
        ];
    }

    public function getById(int $id): array
    {
        $session = ScheduleSession::with(['instructor', 'schedule', 'shift', 'classroom', 'day'])->find($id);

        if (!$session) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Registro no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registro obtenido correctamente',
            'data' => $session
        ];
    }

    public function create(array $data): array
    {
        ScheduleSession::create($data);

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Registro creado correctamente',
        ];
    }

    public function update(array $data, int $id): array
    {
        $session = ScheduleSession::find($id);

        if (!$session) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Registro no encontrado',
            ];
        }

        $sessionData = [];

        if (array_key_exists('instructor_id', $data)) $sessionData['instructor_id'] = $data['instructor_id'];
        if (array_key_exists('shift_id', $data))      $sessionData['shift_id'] = $data['shift_id'];
        if (array_key_exists('classroom_id', $data))  $sessionData['classroom_id'] = $data['classroom_id'];
        if (array_key_exists('day_id', $data))        $sessionData['day_id'] = $data['day_id'];
        if (array_key_exists('start_time', $data))    $sessionData['start_time'] = $data['start_time'];
        if (array_key_exists('end_time', $data))      $sessionData['end_time'] = $data['end_time'];

        // schedule_id NO se actualiza
        if (empty($sessionData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $session->update($sessionData);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registro actualizado correctamente',
        ];
    }

    public function delete(int $id): array
    {
        $session = ScheduleSession::find($id);

        if (!$session) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Registro no encontrado',
            ];
        }

        $session->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registro eliminado correctamente',
        ];
    }
}
