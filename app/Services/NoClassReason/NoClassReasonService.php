<?php

namespace App\Services\NoClassReason;

use App\Events\ResourceChanged;
use App\Models\NoClassReason;
use Illuminate\Support\Facades\Auth;

class NoClassReasonService
{
    public function getAll()
    {
        $data = NoClassReason::orderBy('name', 'asc')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Motivos de días sin clase obtenidos correctamente',
            'data' => $data,
        ];
    }

    public function getById($reasonId)
    {
        $data = NoClassReason::find($reasonId);

        if (!$data) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Motivo no encontrado',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Motivo obtenido correctamente',
            'data' => $data,
        ];
    }

    public function store($data)
    {
        $created = NoClassReason::create($data);

        event(new ResourceChanged(
            'crear',
            NoClassReason::class,
            $created->id,
            Auth::id(),
            'Motivo de día sin clase',
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Motivo creado correctamente',
            'data' => $created,
        ];
    }

    public function update($reasonId, $data)
    {
        $reason = NoClassReason::find($reasonId);

        if (!$reason) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Motivo no encontrado',
                'data' => [],
            ];
        }

        $dataToUpdate = [];

        if (array_key_exists('name', $data)) $dataToUpdate['name'] = $data['name'];
        if (array_key_exists('description', $data)) $dataToUpdate['description'] = $data['description'];

        if (!empty($dataToUpdate)) {
            $reason->update($dataToUpdate);

            event(new ResourceChanged(
                'actualizar',
                NoClassReason::class,
                $reason->id,
                Auth::id(),
                'Motivo de día sin clase',
            ));
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Motivo actualizado correctamente',
            'data' => $reason->fresh(),
        ];
    }

    public function destroy($reasonId)
    {
        $reason = NoClassReason::find($reasonId);

        if (!$reason) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Motivo no encontrado',
                'data' => [],
            ];
        }

        $reason->delete();

        event(new ResourceChanged(
            'eliminar',
            NoClassReason::class,
            $reason->id,
            Auth::id(),
            'Motivo de día sin clase',
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Motivo eliminado correctamente',
            'data' => [],
        ];
    }
}