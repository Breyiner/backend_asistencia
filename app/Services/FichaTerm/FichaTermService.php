<?php

namespace App\Services\FichaTerm;

use App\Events\ResourceChanged;
use App\Models\FichaTerm;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FichaTermService
{
    public function getAll()
    {
        $fichaTerms = FichaTerm::with('ficha', 'term', 'phase')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Trimestres de la ficha obtenidos correctamente',
            'data' => $fichaTerms
        ];
    }

    public function getById($id)
    {
        $fichaTerm = FichaTerm::with('ficha', 'term', 'phase')->find($id);

        if (!$fichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Trimestre de la ficha no encontrado'
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Trimestre de la ficha obtenido con éxito',
            'data' => $fichaTerm
        ];
    }

    public function getByFichaId($fichaId)
    {
        $fichaTerms = FichaTerm::with('ficha', 'term', 'phase')
            ->where('ficha_id', $fichaId)
            ->orderBy('start_date')
            ->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Trimestres de la ficha obtenidos correctamente',
            'data' => $fichaTerms
        ];
    }

    public function create($data)
    {
        $fichaTerm = FichaTerm::create([
            'term_id' => $data['term_id'],
            'ficha_id' => $data['ficha_id'],
            'phase_id' => $data['phase_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        event(new ResourceChanged(
            'crear',
            FichaTerm::class,
            $fichaTerm->id,
            Auth::id(),
            'Trimestre de ficha'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Trimestre de Ficha asignado con éxito',
            'data' => $fichaTerm
        ];
    }

    public function update($data, $id)
    {
        $fichaTerm = FichaTerm::find($id);

        if (!$fichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Trimestre de la ficha no encontrado'
            ];
        }

        $fichaTermData = [];

        if (array_key_exists('term_id', $data)) $fichaTermData['term_id'] = $data['term_id'];
        if (array_key_exists('ficha_id', $data)) $fichaTermData['ficha_id'] = $data['ficha_id'];
        if (array_key_exists('phase_id', $data)) $fichaTermData['phase_id'] = $data['phase_id'];
        if (array_key_exists('start_date', $data)) $fichaTermData['start_date'] = $data['start_date'];
        if (array_key_exists('end_date', $data)) $fichaTermData['end_date'] = $data['end_date'];

        if (empty($fichaTermData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $fichaTerm->update($fichaTermData);

        event(new ResourceChanged(
            'actualizar',
            FichaTerm::class,
            $fichaTerm->id,
            Auth::id(),
            'Trimestre de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Trimestre de la ficha actualizado con éxito",
            "data" => $fichaTerm->fresh()
        ];
    }

    public function setCurrent(int $fichaTermId): array
    {
        try {
            DB::transaction(function () use ($fichaTermId) {
                $fichaTerm = FichaTerm::find($fichaTermId);

                if (!$fichaTerm) {
                    return;
                }

                FichaTerm::where('ficha_id', $fichaTerm->ficha_id)
                    ->update(['is_active' => false]);

                $fichaTerm->update(['is_active' => true]);
            });

            $fichaTerm = FichaTerm::with(['ficha', 'term'])->find($fichaTermId);

            if ($fichaTerm) {
                event(new ResourceChanged(
                    'actualizar',
                    FichaTerm::class,
                    $fichaTermId,
                    Auth::id(),
                    'Trimestre de ficha'
                ));
            }

            return [
                'error' => false,
                'code' => 200,
                'message' => 'Trimestre actual establecido correctamente',
                'data' => $fichaTerm
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'code' => 500,
                'message' => 'Error al establecer trimestre actual: ' . $e->getMessage()
            ];
        }
    }

    public function delete($id)
    {
        $fichaTerm = FichaTerm::find($id);

        if (!$fichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Trimestre de la ficha no encontrado'
            ];
        }

        $fichaTerm->delete();

        event(new ResourceChanged(
            'eliminar',
            FichaTerm::class,
            $id,
            Auth::id(),
            'Trimestre de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Trimestre de la ficha eliminado con éxito",
        ];
    }
}
