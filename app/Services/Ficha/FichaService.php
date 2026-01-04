<?php

namespace App\Services\Ficha;

use App\Models\Ficha;

class FichaService
{
    
    public function getAll()
    {
        $fichas = Ficha::with(['gestor', 'trainingProgram', 'status'])->get();

        if (count($fichas) == 0)
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay fichas registradas",
                "data" => $fichas
            ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fichas obtenidas con éxito",
            "data" => $fichas
        ];

    }

    public function getById($id)
    {
        $ficha = Ficha::with(['gestor', 'trainingProgram', 'status'])->find($id);

        if (!$ficha)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha obtenida con éxito",
            "data" => $ficha
        ];
    }

    public function create(array $data)
    {
        $ficha = Ficha::create([
            'gestor_id' => $data['gestor_id'],
            'ficha_number' => $data['ficha_number'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'training_program_id' => $data['training_program_id'],
            'status_id' => $data['status_id'],
        ]);

        return [
            "error" => false,
            "code" => 201,
            "message" => "Ficha creada con éxito",
            "data" => $ficha
        ];
    }

    public function update($id, array $data)
    {
        $ficha = Ficha::find($id);

        if (!$ficha)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];

        $fichaData = [];

        if(array_key_exists('gestor_id', $data)) {
            $fichaData['gestor_id'] = $data['gestor_id'];
        }

        if(array_key_exists('ficha_number', $data)) {
            $fichaData['ficha_number'] = $data['ficha_number'];
        }

        if(array_key_exists('start_date', $data)) {
            $fichaData['start_date'] = $data['start_date'];
        }

        if(array_key_exists('end_date', $data)) {
            $fichaData['end_date'] = $data['end_date'];
        }

        if(array_key_exists('training_program_id', $data)) {
            $fichaData['training_program_id'] = $data['training_program_id'];
        }

        if(array_key_exists('status_id', $data)) {
            $fichaData['status_id'] = $data['status_id'];
        }

        if(empty($fichaData))
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];

        $ficha->update($fichaData);

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha actualizada con éxito",
            "data" => $ficha
        ];
    }

    public function delete($id)
    {
        $ficha = Ficha::find($id);

        if (!$ficha)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];

        $ficha->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha eliminada con éxito",
        ];
    }
}
