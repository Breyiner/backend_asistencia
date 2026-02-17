<?php

namespace App\Services\FichaStatus;

use App\Events\ResourceChanged;
use App\Models\FichaStatus;
use Illuminate\Support\Facades\Auth;

class FichaStatusService
{
    public static function getAll()
    {
        $statuses = FichaStatus::all();

        if (count($statuses) == 0) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay estados registrados",
                "data" => $statuses
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estados obtenidos con éxito",
            "data" => $statuses
        ];
    }

    public function getStatus($id)
    {
        $status = FichaStatus::find($id);

        if (!$status) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este estado no existe",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estado obtenido con éxito",
            "data" => $status
        ];
    }

    public function createStatus(array $data)
    {
        $status = FichaStatus::create([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        event(new ResourceChanged(
            'crear',
            FichaStatus::class,
            $status->id,
            Auth::id(),
            'Estado de ficha'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Estado creado con éxito',
            'data' => $status,
        ];
    }

    public function updateStatus(array $data, $id)
    {
        $status = FichaStatus::find($id);

        if (!$status) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este estado no existe",
            ];
        }

        $statusData = [];

        if (array_key_exists('name', $data)) {
            $statusData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $statusData['description'] = $data['description'];
        }

        if (empty($statusData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $status->update($statusData);

        event(new ResourceChanged(
            'actualizar',
            FichaStatus::class,
            $status->id,
            Auth::id(),
            'Estado de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estado actualizado con éxito",
            "data" => $status->fresh(),
        ];
    }

    public function deleteStatus($id)
    {
        $status = FichaStatus::find($id);

        if (!$status) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este estado no existe",
            ];
        }

        if ($status->fichas()->exists()) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No se puede eliminar el estado porque tiene fichas asociadas",
            ];
        }

        $status->delete();

        event(new ResourceChanged(
            'eliminar',
            FichaStatus::class,
            $id,
            Auth::id(),
            'Estado de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estado eliminado con éxito",
        ];
    }
}
