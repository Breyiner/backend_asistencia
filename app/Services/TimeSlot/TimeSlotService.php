<?php

namespace App\Services\TimeSlot;

use App\Events\ResourceChanged;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\Auth;

class TimeSlotService
{
    public function getAll()
    {
        $items = TimeSlot::orderBy('start_time', 'asc')->get();

        if ($items->count() === 0) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay franjas horarias registradas",
                "data" => [],
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Franjas horarias obtenidas con éxito",
            "data" => $items,
        ];
    }

    public function getById($id)
    {
        $item = TimeSlot::find($id);

        if (!$item) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta franja horaria no existe",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Franja horaria obtenida con éxito",
            "data" => $item,
        ];
    }

    public function create(array $data)
    {
        $code = strtoupper($data['code']);

        $item = TimeSlot::create([
            'code' => $code,
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        event(new ResourceChanged(
            'crear',
            TimeSlot::class,
            $item->id,
            Auth::id(),
            'Franja horaria'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Franja horaria creada con éxito',
            'data' => $item,
        ];
    }

    public function update(array $data, $id)
    {
        $item = TimeSlot::find($id);

        if (!$item) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta franja horaria no existe",
            ];
        }

        $payload = [];

        if (array_key_exists('code', $data)) $payload['code'] = strtoupper($data['code']);
        if (array_key_exists('name', $data)) $payload['name'] = $data['name'];
        if (array_key_exists('start_time', $data)) $payload['start_time'] = $data['start_time'];
        if (array_key_exists('end_time', $data)) $payload['end_time'] = $data['end_time'];

        if (empty($payload)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        if (isset($payload['start_time'], $payload['end_time']) && $payload['start_time'] >= $payload['end_time']) {
            return [
                "error" => true,
                "code" => 422,
                "message" => "La hora inicial debe ser menor que la hora final",
            ];
        }

        $item->update($payload);

        event(new ResourceChanged(
            'actualizar',
            TimeSlot::class,
            $item->id,
            Auth::id(),
            'Franja horaria'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Franja horaria actualizada con éxito",
            "data" => $item->fresh(),
        ];
    }

    public function delete($id)
    {
        $item = TimeSlot::find($id);

        if (!$item) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta franja horaria no existe",
            ];
        }

        $item->delete();

        event(new ResourceChanged(
            'eliminar',
            TimeSlot::class,
            $id,
            Auth::id(),
            'Franja horaria'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Franja horaria eliminada con éxito",
        ];
    }
}