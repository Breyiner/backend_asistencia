<?php

namespace App\Services\Phase;

use App\Events\ResourceChanged;
use App\Models\Phase;
use Illuminate\Support\Facades\Auth;

class PhaseService
{
    public static function getAll()
    {
        $phases = Phase::all();

        if (count($phases) == 0) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay fases registradas",
                "data" => $phases,
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fases obtenidas con éxito",
            "data" => $phases,
        ];
    }

    public function getById($id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta fase no existe",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fase obtenida con éxito",
            "data" => $phase,
        ];
    }

    public function create(array $data)
    {
        $phase = Phase::create([
            'name' => $data['name'],
        ]);

        event(new ResourceChanged(
            'crear',
            Phase::class,
            $phase->id,
            Auth::id(),
            'Fase'
        ));

        return [
            "error" => false,
            "code" => 201,
            "message" => "Fase creada con éxito",
            "data" => $phase,
        ];
    }

    public function update(array $data, $id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta fase no existe",
            ];
        }

        $phaseData = [];

        if (array_key_exists('name', $data)) {
            $phaseData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $phaseData['description'] = $data['description'];
        }

        if (!empty($phaseData)) {
            $phase->update($phaseData);

            event(new ResourceChanged(
                'actualizar',
                Phase::class,
                $phase->id,
                Auth::id(),
                'Fase'
            ));
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fase actualizada con éxito",
            "data" => $phase->fresh(),
        ];
    }

    public function delete($id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta fase no existe",
            ];
        }

        $phase->delete();

        event(new ResourceChanged(
            'eliminar',
            Phase::class,
            $id,
            Auth::id(),
            'Fase'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fase eliminada con éxito",
        ];
    }
}