<?php

namespace App\Services\Day;

use App\Events\ResourceChanged;
use App\Models\Day;
use Illuminate\Support\Facades\Auth;

class DayService
{
    public function getAll(): array
    {
        $days = Day::orderBy('day_number')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Días obtenidos correctamente',
            'data' => $days
        ];
    }

    public function getById(int $id): array
    {
        $day = Day::find($id);

        if (!$day) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día no encontrado'
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día obtenido con éxito',
            'data' => $day
        ];
    }

    public function create(array $data): array
    {
        $day = Day::create($data);

        event(new ResourceChanged(
            'crear',
            Day::class,
            $day->id,
            Auth::id(),
            'Día'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Día creado correctamente',
            'data' => $day
        ];
    }

    public function update(array $data, int $id): array
    {
        $day = Day::find($id);

        if (!$day) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día no encontrado'
            ];
        }

        $dayData = [];

        if (array_key_exists('name', $data)) {
            $dayData['name'] = $data['name'];
        }
        if (array_key_exists('day_number', $data)) {
            $dayData['day_number'] = $data['day_number'];
        }

        if (empty($dayData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $day->update($dayData);

        event(new ResourceChanged(
            'actualizar',
            Day::class,
            $day->id,
            Auth::id(),
            'Día'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día actualizado con éxito',
            'data' => $day->fresh()
        ];
    }

    public function delete(int $id): array
    {
        $day = Day::find($id);

        if (!$day) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día no encontrado'
            ];
        }

        $day->delete();

        event(new ResourceChanged(
            'eliminar',
            Day::class,
            $id,
            Auth::id(),
            'Día'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día eliminado correctamente'
        ];
    }
}
