<?php

namespace App\Services\TrainingProgram;

use App\Events\ResourceChanged;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\Auth;

class TrainingProgramService
{
    public function getAll($perPage = 10)
    {
        $query = TrainingProgram::select([
            'id',
            'name',
            'duration',
            'area_id',
            'qualification_level_id',
        ])
            ->with([
                'area:id,name',
                'qualificationLevel:id,name',
            ])
            ->withCount('fichas');

        // Filtros
        if (request()->filled('program_name')) {
            $query->where('name', 'like', '%' . request('program_name') . '%');
        }

        if (request()->filled('area_name')) {
            $query->whereHas('area', function ($q) {
                $q->where('name', 'like', '%' . request('area_name') . '%');
            });
        }

        if (request()->filled('qualification_level_name')) {
            $query->whereHas('qualificationLevel', function ($q) {
                $q->where('name', 'like', '%' . request('qualification_level_name') . '%');
            });
        }

        $programs = $query->paginate($perPage);

        $items = $programs->getCollection()->map(function ($program) {
            return [
                'id' => $program->id,
                'name' => $program->name,
                'area_name' => $program->area?->name ?? 'Sin área',
                'qualification_level_name' => $program->qualificationLevel?->name ?? 'Sin titulación',
                'fichas_count' => (int) ($program->fichas_count ?? 0),
                'duration' => ($program->duration ?? 0) . ' meses',
            ];
        });

        if ($items->isEmpty()) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay programas de formación registrados",
                "data" => $items,
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Programas de formación obtenidos exitosamente",
            "data" => $items,
            "paginate" => [
                "current_page" => $programs->currentPage(),
                "per_page" => $programs->perPage(),
                "total" => $programs->total(),
                "last_page" => $programs->lastPage(),
                "from" => $programs->firstItem(),
                "to" => $programs->lastItem(),
            ],
        ];
    }


    public function getById($id)
    {
        $program = TrainingProgram::with([
            'area:id,name',
            'qualificationLevel:id,name',
        ])
            ->withCount(['fichas', 'apprentices'])
            ->find($id);

        if (!$program) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Programa de formación no encontrado",
            ];
        }

        $duration = (int) ($program->duration ?? 0);
        $trimestersLective = (int) max(0, ($duration - 6) / 3);

        $item = [
            'id' => $program->id,
            'name' => $program->name,
            'area_id' => $program->area_id,
            'area_name' => $program->area?->name ?? 'Sin área',
            'qualification_level_id' => $program->qualification_level_id,
            'qualification_level_name' => $program->qualificationLevel?->name ?? 'Sin titulación',
            'description' => $program->description,
            'fichas_count' => (int) ($program->fichas_count ?? 0),
            'apprentices_count' => (int) ($program->apprentices_count ?? 0),
            'duration' => $duration,
            'trimesters_lective' => $trimestersLective,
            'created_at' => $program->created_at?->toDateString(),
            'updated_at' => $program->updated_at?->toDateString(),
        ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Programa de formación obtenido exitosamente",
            "data" => $item,
        ];
    }



    public function create(array $data)
    {
        $program = TrainingProgram::create(
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'duration' => $data['duration'],
                'qualification_level_id' => $data['qualification_level_id'],
                'area_id' => $data['area_id'],
            ]
        );

        event(new ResourceChanged(
            'crear',
            TrainingProgram::class,
            $program->id,
            Auth::id(),
            'Programa de formación'
        ));

        return [
            "error" => false,
            "code" => 201,
            "message" => "Programa de formación creado exitosamente",
            "data" => $program,
        ];
    }

    public function update(array $data, $id)
    {
        $program = TrainingProgram::find($id);

        if (!$program) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Programa de formación no encontrado",
            ];
        }

        $programData = [];

        if (array_key_exists('name', $data)) {
            $programData['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            echo $data['description'];
            $programData['description'] = $data['description'] ?? null;
        }
        if (array_key_exists('duration', $data)) {
            $programData['duration'] = $data['duration'];
        }
        if (array_key_exists('qualification_level_id', $data)) {
            $programData['qualification_level_id'] = $data['qualification_level_id'];
        }
        if (array_key_exists('area_id', $data)) {
            $programData['area_id'] = $data['area_id'];
        }

        if (empty($programData)) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay datos para actualizar",
                "data" => $program,
            ];
        }

        $program->update($programData);

        event(new ResourceChanged(
            'actualizar',
            TrainingProgram::class,
            $program->id,
            Auth::id(),
            'Programa de formación'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Programa de formación actualizado exitosamente",
            "data" => $program,
        ];
    }

    public function delete($id)
    {
        $program = TrainingProgram::find($id);

        if (!$program) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Programa de formación no encontrado",
            ];
        }

        if ($program->fichas()->exists()) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No se puede eliminar el programa de formación porque tiene fichas asociadas",
            ];
        }

        $program->delete();

        event(new ResourceChanged(
            'eliminar',
            TrainingProgram::class,
            $program->id,
            Auth::id(),
            'Programa de formación'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Programa de formación eliminado exitosamente",
        ];
    }
}
