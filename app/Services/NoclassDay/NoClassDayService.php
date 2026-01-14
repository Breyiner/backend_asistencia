<?php

namespace App\Services\NoClassDay;

use App\Events\ResourceChanged;
use App\Models\NoClassDay;
use Illuminate\Support\Facades\Auth;

class NoClassDayService
{
    public function getAll()
    {
        $data = NoClassDay::orderBy('date', 'desc')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Días sin clase obtenidos correctamente',
            'data' => $data,
        ];
    }

    public function getById($noClassDayId)
    {
        $data = NoClassDay::find($noClassDayId);

        if (!$data) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día sin clase no encontrado',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día sin clase obtenido correctamente',
            'data' => $data,
        ];
    }

    public function store($data)
    {
        $created = NoClassDay::create($data);

        event(new ResourceChanged(
            'crear',
            NoClassDay::class,
            $created->id,
            Auth::id(),
            'Día sin clase',
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Día sin clase creado correctamente',
            'data' => $created,
        ];
    }

    public function update($noClassDayId, $data)
    {
        $noClassDay = NoClassDay::find($noClassDayId);

        if (!$noClassDay) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día sin clase no encontrado',
                'data' => [],
            ];
        }

        $dataToUpdate = [];

        if (array_key_exists('ficha_id', $data)) $dataToUpdate['ficha_id'] = $data['ficha_id'];
        if (array_key_exists('date', $data)) $dataToUpdate['date'] = $data['date'];
        if (array_key_exists('reason', $data)) $dataToUpdate['reason'] = $data['reason'];

        if (!empty($dataToUpdate)) {
            $noClassDay->update($dataToUpdate);

            event(new ResourceChanged(
                'actualizar',
                NoClassDay::class,
                $noClassDay->id,
                Auth::id(),
                'Día sin clase',
            ));
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día sin clase actualizado correctamente',
            'data' => $noClassDay->fresh(),
        ];
    }

    public function destroy($noClassDayId)
    {
        $noClassDay = NoClassDay::find($noClassDayId);

        if (!$noClassDay) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día sin clase no encontrado',
                'data' => [],
            ];
        }

        $noClassDay->delete();

        event(new ResourceChanged(
            'eliminar',
            NoClassDay::class,
            $noClassDay->id,
            Auth::id(),
            'Día sin clase',
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día sin clase eliminado correctamente',
            'data' => [],
        ];
    }

    public function checkByFichaAndDate($fichaId, $date)
    {
        $exists = NoClassDay::query()
            ->where('ficha_id', $fichaId)
            ->whereDate('date', $date)
            ->exists();

        return [
            'error' => false,
            'code' => 200,
            'message' => $exists
                ? 'La fecha está marcada como día sin clase.'
                : 'La fecha no está marcada como día sin clase.',
            'data' => [
                'ficha_id' => (int) $fichaId,
                'date' => $date,
                'is_no_class_day' => $exists,
            ],
        ];
    }
}
