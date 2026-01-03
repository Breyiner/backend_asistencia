<?php

namespace App\Services\QualificationLevel;

use App\Models\QualificationLevel;

class QualificationLevelService
{
    public function getAll()
    {
        $items = QualificationLevel::all();

        if (count($items) === 0) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay niveles de formación registrados",
                "data" => $items,
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Niveles de formación obtenidos con éxito",
            "data" => $items,
        ];
    }

    public function getById($id)
    {
        $item = QualificationLevel::find($id);

        if (!$item) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este nivel de formación no existe",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Nivel de formación obtenido con éxito",
            "data" => $item,
        ];
    }

    public function create(array $data)
    {
        QualificationLevel::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return [
            "error" => false,
            "code" => 201,
            "message" => "Nivel de formación creado con éxito",
        ];
    }

    public function update(array $data, $id)
    {
        $item = QualificationLevel::find($id);

        if (!$item) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este nivel de formación no existe",
            ];
        }

        $payload = [];

        if (array_key_exists('name', $data)) $payload['name'] = $data['name'];
        if (array_key_exists('description', $data)) $payload['description'] = $data['description'];

        if (!empty($payload)) $item->update($payload);

        return [
            "error" => false,
            "code" => 200,
            "message" => "Nivel de formación actualizado con éxito",
        ];
    }

    public function delete($id)
    {
        $item = QualificationLevel::find($id);

        if (!$item) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este nivel de formación no existe",
            ];
        }

        $item->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Nivel de formación eliminado con éxito",
        ];
    }
}
