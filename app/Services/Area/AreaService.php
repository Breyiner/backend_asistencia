<?php

namespace App\Services\Area;

use App\Models\Area;

class AreaService
{
    public static function getAll()
    {
        $areas = Area::all();

        if (count($areas) == 0) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay áreas registradas",
                "data" => $areas,
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Áreas obtenidas con éxito",
            "data" => $areas,
        ];
    }

    public function getArea($id)
    {
        $area = Area::find($id);

        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área obtenida con éxito",
            "data" => $area,
        ];
    }

    public function createArea(array $data)
    {
        Area::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]); // Eloquent: create model [web:46]

        return [
            "error" => false,
            "code" => 201,
            "message" => "Área creada con éxito",
        ];
    }

    public function updateArea(array $data, $id)
    {
        $area = Area::find($id);

        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        $areaData = [];

        if (array_key_exists('name', $data)) {
            $areaData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $areaData['description'] = $data['description'];
        }

        if (!empty($areaData)) {
            $area->update($areaData);
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área actualizada con éxito",
        ];
    }

    public function deleteArea($id)
    {
        $area = Area::find($id);

        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        if($area->trainingPrograms()->count() > 0){
            return [
                "error" => true,
                "code" => 400,
                "message" => "No se puede eliminar esta área porque tiene programas de formación asociados",
            ];
        }

        $area->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área eliminada con éxito",
        ];
    }
}
