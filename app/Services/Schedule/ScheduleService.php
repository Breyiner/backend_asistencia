<?php

namespace App\Services\Schedule;

use App\Events\ResourceChanged;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;

class ScheduleService
{
    public function getAll()
    {
        $schedules = Schedule::with('fichaTerm')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Horarios obtenidos correctamente',
            'data' => $schedules
        ];
    }

    public function getById($id): array
    {
        $schedule = Schedule::with('fichaTerm')->find($id);

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Horario no encontrado'
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Horario obtenido con éxito',
            'data' => $schedule
        ];
    }

    public function create(array $data): array
    {
        $schedule = Schedule::create([
            'description' => $data['description'] ?? null,
            'ficha_term_id' => $data['ficha_term_id'],
        ]);

        event(new ResourceChanged(
            'crear',
            Schedule::class,
            $schedule->id,
            Auth::id(),
            'Horario'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Horario creado correctamente',
            'data' => $schedule
        ];
    }

    public function update($data, $id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Horario no encontrado'
            ];
        }

        $scheduleData = [];

        if (array_key_exists('description', $data)) {
            $scheduleData['description'] = $data['description'];
        }

        if (empty($scheduleData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $schedule->update($scheduleData);

        event(new ResourceChanged(
            'actualizar',
            Schedule::class,
            $schedule->id,
            Auth::id(),
            'Horario'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Trimestre de la ficha actualizado con éxito",
            "data" => $schedule->fresh()
        ];
    }

    public function delete(int $id): array
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Horario no encontrado'
            ];
        }

        $schedule->delete();

        event(new ResourceChanged(
            'eliminar',
            Schedule::class,
            $id,
            Auth::id(),
            'Horario'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Horario eliminado correctamente'
        ];
    }
}
