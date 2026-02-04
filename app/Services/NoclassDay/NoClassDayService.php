<?php

namespace App\Services\NoClassDay;

use App\Events\ResourceChanged;
use App\Models\NoClassDay;
use Illuminate\Support\Facades\Auth;

class NoClassDayService
{
    public function getAll($perPage = 10)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        $query = NoClassDay::select([
            'id',
            'ficha_id',
            'reason_id',
            'date',
            'observations',
            'created_at',
            'updated_at',
        ])->with([
            'ficha:id,ficha_number,training_program_id,shift_id,gestor_id',
            'ficha.trainingProgram:id,name',
            'reason:id,name,description',
        ]);

        // Aplicar alcances según el rol
        if ($roleCode === 'COORDINADOR') {
            $query->whereHas('ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            $query->whereHas('ficha', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        } elseif ($roleCode === 'INSTRUCTOR') {
            $query->whereHas('ficha.currentFichaTerm', function ($q) use ($userId) {
                $q->where('is_current', 1)
                    ->whereHas('schedule.scheduleSessions', function ($subQ) use ($userId) {
                        $subQ->where('instructor_id', $userId);
                    });
            });
        }
        // Si es ADMIN, no se aplica ningún filtro de alcance

        // Filtro por ficha_id
        if (request()->filled('ficha_id')) {
            $query->where('ficha_id', request('ficha_id'));
        }

        // Filtro por número de ficha
        if (request()->filled('ficha_number')) {
            $query->whereHas('ficha', function ($q) {
                $q->where('ficha_number', 'like', '%' . request('ficha_number') . '%');
            });
        }

        // Filtro por motivo
        if (request()->filled('reason_id')) {
            $query->where('reason_id', request('reason_id'));
        }

        // Filtro por nombre del motivo
        if (request()->filled('reason_name')) {
            $query->whereHas('reason', function ($q) {
                $q->where('name', 'like', '%' . request('reason_name') . '%');
            });
        }

        // Filtro por fecha específica
        if (request()->filled('date')) {
            $query->whereDate('date', request('date'));
        }

        // Filtro por rango de fechas
        if (request()->filled('date_from')) {
            $query->whereDate('date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('date', '<=', request('date_to'));
        }

        // Filtro por programa de formación
        if (request()->filled('training_program_id')) {
            $query->whereHas('ficha', function ($q) {
                $q->where('training_program_id', request('training_program_id'));
            });
        }

        $noClassDays = $query->orderBy('date', 'desc')->paginate($perPage);

        $items = $noClassDays->getCollection()->map(function ($noClassDay) {
            return [
                'id' => $noClassDay->id,
                'date' => $noClassDay->date,

                'ficha_id' => $noClassDay->ficha_id,
                'ficha_number' => $noClassDay->ficha?->ficha_number ?? 'Sin ficha',

                'training_program_id' => $noClassDay->ficha?->training_program_id,
                'training_program_name' => $noClassDay->ficha?->trainingProgram?->name ?? 'Sin programa',

                'reason_id' => $noClassDay->reason_id,
                'reason_name' => $noClassDay->reason?->name ?? 'Sin motivo',
                'reason_description' => $noClassDay->reason?->description ?? '',

                'observations' => $noClassDay->observations,

                'created_at' => $noClassDay->created_at?->toDateString(),
                'updated_at' => $noClassDay->updated_at?->toDateString(),
            ];
        })->values();

        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay días sin clase registrados',
                'data' => [],
                'paginate' => [
                    'current_page' => $noClassDays->currentPage(),
                    'per_page' => $noClassDays->perPage(),
                    'total' => $noClassDays->total(),
                    'last_page' => $noClassDays->lastPage(),
                    'from' => $noClassDays->firstItem(),
                    'to' => $noClassDays->lastItem(),
                ],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Días sin clase obtenidos correctamente',
            'data' => $items,
            'paginate' => [
                'current_page' => $noClassDays->currentPage(),
                'per_page' => $noClassDays->perPage(),
                'total' => $noClassDays->total(),
                'last_page' => $noClassDays->lastPage(),
                'from' => $noClassDays->firstItem(),
                'to' => $noClassDays->lastItem(),
            ],
        ];
    }

    public function getById($noClassDayId)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        $query = NoClassDay::select([
            'id',
            'ficha_id',
            'reason_id',
            'date',
            'observations',
            'created_at',
            'updated_at',
        ])->with([
            'ficha:id,ficha_number,training_program_id,shift_id,gestor_id',
            'ficha.trainingProgram:id,name,coordinator_id',
            'ficha.shift:id,name',
            'ficha.gestor.profile:id,user_id,first_name,last_name',
            'reason:id,name,description',
        ]);

        // Aplicar alcances según el rol
        if ($roleCode === 'COORDINADOR') {
            $query->whereHas('ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            $query->whereHas('ficha', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        } elseif ($roleCode === 'INSTRUCTOR') {
            $query->whereHas('ficha.currentFichaTerm', function ($q) use ($userId) {
                $q->where('is_current', 1)
                    ->whereHas('schedule.scheduleSessions', function ($subQ) use ($userId) {
                        $subQ->where('instructor_id', $userId);
                    });
            });
        }
        // Si es ADMIN, no se aplica ningún filtro de alcance

        $noClassDay = $query->find($noClassDayId);

        if (!$noClassDay) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día sin clase no encontrado',
                'data' => [],
            ];
        }

        $gestorName = $noClassDay->ficha?->gestor?->profile
            ? trim($noClassDay->ficha->gestor->profile->first_name . ' ' . $noClassDay->ficha->gestor->profile->last_name)
            : 'Sin gestor';

        $data = [
            'id' => $noClassDay->id,
            'date' => $noClassDay->date,

            'ficha_id' => $noClassDay->ficha_id,
            'ficha_number' => $noClassDay->ficha?->ficha_number ?? 'Sin ficha',

            'gestor_id' => $noClassDay->ficha?->gestor_id,
            'gestor_name' => $gestorName,

            'training_program_id' => $noClassDay->ficha?->training_program_id,
            'training_program_name' => $noClassDay->ficha?->trainingProgram?->name ?? 'Sin programa',

            'shift_id' => $noClassDay->ficha?->shift_id,
            'shift_name' => $noClassDay->ficha?->shift?->name ?? 'Sin jornada',

            'reason_id' => $noClassDay->reason_id,
            'reason_name' => $noClassDay->reason?->name ?? 'Sin motivo',
            'reason_description' => $noClassDay->reason?->description ?? '',

            'observations' => $noClassDay->observations,

            'created_at' => $noClassDay->created_at?->toDateString(),
            'updated_at' => $noClassDay->updated_at?->toDateString(),
        ];

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
        if (array_key_exists('reason_id', $data)) $dataToUpdate['reason_id'] = $data['reason_id'];
        if (array_key_exists('observations', $data)) $dataToUpdate['observations'] = $data['observations'];

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
