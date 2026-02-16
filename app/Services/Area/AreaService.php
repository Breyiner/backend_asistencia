<?php

namespace App\Services\Area;

use App\Events\ResourceChanged;
use App\Models\Area;
use Illuminate\Support\Facades\Auth;

class AreaService
{
    public function getAll($perPage = 10)
    {
        $query = Area::query()
            ->select(['id', 'name', 'description', 'created_at', 'updated_at'])
            // Ajusta estos withCount según tus relaciones reales en Area
            ->withCount(['trainingPrograms']);

        // Filtros
        if (request()->filled('area_name')) {
            $query->where('name', 'like', '%' . request('area_name') . '%');
        }

        if (request()->filled('description')) {
            $query->where('description', 'like', '%' . request('description') . '%');
        }

        $areas = $query->orderBy('name', 'asc')->paginate($perPage);

        $items = $areas->getCollection()->map(function ($area) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'training_programs_count' => (int) ($area->training_programs_count ?? 0),
                'created_at' => $area->created_at?->toDateString(),
                'updated_at' => $area->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay áreas registradas",
                "data" => $items,
                "paginate" => [
                    "current_page" => $areas->currentPage(),
                    "per_page" => $areas->perPage(),
                    "total" => $areas->total(),
                    "last_page" => $areas->lastPage(),
                    "from" => $areas->firstItem(),
                    "to" => $areas->lastItem(),
                ],
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Áreas obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $areas->currentPage(),
                "per_page" => $areas->perPage(),
                "total" => $areas->total(),
                "last_page" => $areas->lastPage(),
                "from" => $areas->firstItem(),
                "to" => $areas->lastItem(),
            ],
        ];
    }

    public function getAllForSelect()
    {
        $query = Area::query()
            ->select(['id', 'name'])
            ->orderBy('name', 'asc');

        if (request()->filled('area_name')) {
            $query->where('name', 'like', '%' . request('area_name') . '%');
        }

        $areas = $query->get();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Áreas obtenidas con éxito",
            "data" => $areas,
        ];
    }

    public function getById($id)
    {
        $area = Area::query()
            ->select(['id', 'name', 'description', 'created_at', 'updated_at'])
            ->withCount(['trainingPrograms'])
            ->find($id);

        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        $item = [
            'id' => $area->id,
            'name' => $area->name,
            'description' => $area->description,
            'training_programs_count' => (int) ($area->training_programs_count ?? 0),
            'created_at' => $area->created_at?->toDateString(),
            'updated_at' => $area->updated_at?->toDateString(),
        ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área obtenida con éxito",
            "data" => $item,
        ];
    }

    public function create(array $data)
    {
        $area = Area::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        event(new ResourceChanged(
            'crear',
            Area::class,
            $area->id,
            Auth::id(),
            'Área'
        ));

        return [
            "error" => false,
            "code" => 201,
            "message" => "Área creada con éxito",
            "data" => $area,
        ];
    }

    public function update(array $data, $id)
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
            $areaData['description'] = $data['description'] ?? null;
        }

        if (empty($areaData)) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay datos para actualizar",
                "data" => $area,
            ];
        }

        $area->update($areaData);

        event(new ResourceChanged(
            'actualizar',
            Area::class,
            $area->id,
            Auth::id(),
            'Área'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área actualizada con éxito",
            "data" => $area->fresh(),
        ];
    }

    public function delete($id)
    {
        $area = Area::find($id);

        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        // Más eficiente que count() cuando solo necesitas saber si existen relacionados
        if ($area->trainingPrograms()->exists()) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No se puede eliminar esta área porque tiene programas de formación asociados",
            ];
        }

        $area->delete();

        event(new ResourceChanged(
            'eliminar',
            Area::class,
            $id,
            Auth::id(),
            'Área'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área eliminada con éxito",
        ];
    }
}
