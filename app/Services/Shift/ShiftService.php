<?php

namespace App\Services\Shift;

use App\Events\ResourceChanged;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;

class ShiftService
{
    public function getAll(): array
    {
        $shifts = Shift::orderBy('start_time')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Jornadas obtenidas correctamente',
            'data' => $shifts
        ];
    }

    public function getById(int $id): array
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Jornada no encontrada',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Jornada obtenida correctamente',
            'data' => $shift
        ];
    }

    public function create(array $data): array
    {

        $shift = Shift::create($data);

        event(new ResourceChanged(
            'crear',
            Shift::class,
            $shift->id,
            Auth::id(),
            'Jornada'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Jornada creada correctamente',
        ];
    }

    public function update(array $data, int $id): array
    {
        $shift = Shift::find($id);
        if (!$shift) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Jornada no encontrada',
            ];
        }

        $shiftData = [];

        if (array_key_exists('name', $data)) {
            $shiftData['name'] = $data['name'];
        }

        if (array_key_exists('start_time', $data)) {
            $shiftData['start_time'] = $data['start_time'];
        }

        if (array_key_exists('end_time', $data)) {
            $shiftData['end_time'] = $data['end_time'];
        }

        if (empty($shiftData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $shift->update($shiftData);

        event(new ResourceChanged(
            'actualizar',
            Shift::class,
            $shift->id,
            Auth::id(),
            'Jornada'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Jornada actualizada correctamente',
        ];
    }

    public function delete(int $id): array
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Jornada no encontrada',
            ];
        }

        $shift->delete();

        event(new ResourceChanged(
            'eliminar',
            Shift::class,
            $shift->id,
            Auth::id(),
            'Jornada'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Jornada eliminada correctamente',
        ];
    }
}
