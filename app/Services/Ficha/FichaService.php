<?php

namespace App\Services\Ficha;

use App\Events\ResourceChanged;
use App\Models\Ficha;
use Illuminate\Support\Facades\Auth;

class FichaService
{
    public function getAll($perPage = 10)
    {
        $query = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'ficha_number',
            'created_at',
            'updated_at',
        ])->with([
            'gestor.profile:id,user_id,first_name,last_name',
            'trainingProgram:id,name',
            'status:id,name',

            'currentFichaTerm:id,ficha_id,term_id,is_current',
            'currentFichaTerm.term:id,name',
        ])->withCount('apprentices');

        if (request()->filled('ficha_number')) {
            $query->where('ficha_number', 'like', '%' . request('ficha_number') . '%');
        }

        if (request()->filled('training_program_name')) {
            $query->whereHas('trainingProgram', function ($q) {
                $q->where('name', 'like', '%' . request('training_program_name') . '%');
            });
        }

        if (request()->filled('status_name')) {
            $query->whereHas('status', function ($q) {
                $q->where('name', 'like', '%' . request('status_name') . '%');
            });
        }

        if (request()->filled('term_name')) {
            $query->whereHas('currentFichaTerm.term', function ($q) {
                $q->where('name', 'like', '%' . request('term_name') . '%');
            });
        }

        $fichas = $query->paginate($perPage);

        $items = $fichas->getCollection()->map(function ($ficha) {
            return [
                'id' => $ficha->id,
                'ficha_number' => $ficha->ficha_number,

                'gestor_id' => $ficha->gestor_id,
                'gestor_name' => $ficha->gestor?->profile
                    ? $ficha->gestor->profile->first_name . ' ' . $ficha->gestor->profile->last_name
                    : 'Sin gestor',

                'training_program_id' => $ficha->training_program_id,
                'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',

                'status_id' => $ficha->status_id,
                'status_name' => $ficha->status?->name ?? 'Sin estado',

                'current_term_id' => $ficha->currentFichaTerm?->term?->id,
                'current_term_name' => $ficha->currentFichaTerm?->term?->name ?? 'Sin trimestre actual',

                'apprentices_count' => (int) ($ficha->apprentices_count ?? 0),

                'created_at' => $ficha->created_at?->toDateString(),
                'updated_at' => $ficha->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay fichas registradas",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fichas obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $fichas->currentPage(),
                "per_page" => $fichas->perPage(),
                "total" => $fichas->total(),
                "last_page" => $fichas->lastPage(),
                "from" => $fichas->firstItem(),
                "to" => $fichas->lastItem(),
            ],
        ];
    }


    public function getById($id)
    {
        $ficha = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'ficha_number',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
        ])
            ->with([
                'gestor.profile:id,user_id,first_name,last_name',
                'trainingProgram:id,name',
                'status:id,name',

                'currentFichaTerm:id,ficha_id,term_id,phase_id,start_date,end_date,is_current',
                'currentFichaTerm.term:id,name',
                'currentFichaTerm.phase:id,name',

                'fichaTerms' => function ($q) {
                    $q->select([
                        'id',
                        'ficha_id',
                        'term_id',
                        'phase_id',
                        'start_date',
                        'end_date',
                        'is_current',
                    ])
                        ->orderBy('start_date', 'asc');
                },
                'fichaTerms.term:id,name',
                'fichaTerms.phase:id,name',
                'fichaTerms.schedule:id,ficha_term_id',
            ])
            ->withCount('apprentices')
            ->find($id);

        if (!$ficha) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];
        }

        $gestorName = $ficha->gestor?->profile
            ? trim($ficha->gestor->profile->first_name . ' ' . $ficha->gestor->profile->last_name)
            : 'Sin gestor';

        $data = [
            'id' => $ficha->id,
            'ficha_number' => $ficha->ficha_number,

            'gestor_id' => $ficha->gestor_id,
            'gestor_name' => $gestorName,

            'training_program_id' => $ficha->training_program_id,
            'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',

            'apprentices_count' => (int) ($ficha->apprentices_count ?? 0),

            'status_id' => $ficha->status_id,
            'status_name' => $ficha->status?->name ?? 'Sin estado',

            'start_date' => $ficha->start_date?->toDateString(),
            'end_date' => $ficha->end_date?->toDateString(),

            'current_ficha_term_id' => $ficha->currentFichaTerm?->id ?? null,
            'current_term_name' => $ficha->currentFichaTerm?->term?->name ?? 'Sin trimestre actual',
            'current_phase_name' => $ficha->currentFichaTerm?->phase?->name ?? null,

            'ficha_terms' => $ficha->fichaTerms->map(function ($ft) {
                return [
                    'id' => $ft->id,
                    'term_id' => $ft->term_id,
                    'term_name' => $ft->term?->name ?? null,
                    'phase_id' => $ft->phase_id,
                    'phase_name' => $ft->phase?->name ?? null,
                    'start_date' => $ft->start_date?->toDateString(),
                    'end_date' => $ft->end_date?->toDateString(),
                    'is_current' => (bool) $ft->is_current,
                ];
            })->values(),

            'created_at' => $ficha->created_at?->toDateString(),
            'updated_at' => $ficha->updated_at?->toDateString(),
        ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha obtenida con éxito",
            "data" => $data
        ];
    }

    public function getByTrainingProgram($trainingProgramId)
    {
        $query = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'ficha_number',
            'created_at',
            'updated_at',
        ])
            ->with([
                'gestor.profile:id,user_id,first_name,last_name',
                'trainingProgram:id,name',
                'status:id,name',
            ])
            ->where('training_program_id', $trainingProgramId);

        $fichas = $query->paginate(10);

        $items = $fichas->getCollection()->map(function ($ficha) {
            return [
                'id' => $ficha->id,
                'number' => $ficha->ficha_number,
                'gestor_id' => $ficha->gestor_id,
                'gestor_name' => $ficha->gestor?->profile
                    ? $ficha->gestor->profile->first_name . ' ' . $ficha->gestor->profile->last_name
                    : 'Sin gestor',
                'training_program_id' => $ficha->training_program_id,
                'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',
                'status_id' => $ficha->status_id,
                'status_name' => $ficha->status?->name ?? 'Sin estado',
                'created_at' => $ficha->created_at?->toDateString(),
                'updated_at' => $ficha->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "No hay fichas para este programa",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fichas del programa obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $fichas->currentPage(),
                "per_page" => $fichas->perPage(),
                "total" => $fichas->total(),
                "last_page" => $fichas->lastPage(),
                "from" => $fichas->firstItem(),
                "to" => $fichas->lastItem(),
            ],
        ];
    }

    public function availableForRealClass()
    {
        $userId = Auth::id();

        $query = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'ficha_number',
            'created_at',
            'updated_at',
        ])->with([
            'trainingProgram:id,name',
            'currentFichaTerm:id,ficha_id,term_id,is_current',
            'currentFichaTerm.term:id,name',
        ])->whereHas('currentFichaTerm', fn($q) => $q->where('is_current', 1))
            ->where(function ($q) use ($userId) {
                $q->where('gestor_id', $userId)

                    ->orWhereHas('currentFichaTerm.schedule.scheduleSessions', function ($qq) use ($userId) {
                        $qq->where('instructor_id', $userId);
                    });
            });

        $fichas = $query->orderBy('ficha_number', 'asc')->get();

        $items = $fichas->map(function ($ficha) {
            return [
                'id' => $ficha->id,
                'ficha_number' => $ficha->ficha_number,
                'training_program_id' => $ficha->training_program_id,
                'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',
                'current_term_id' => $ficha->currentFichaTerm?->term?->id,
                'current_term_name' => $ficha->currentFichaTerm?->term?->name ?? 'Sin trimestre actual',
            ];
        })->values();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fichas disponibles obtenidas con éxito',
            'data' => $items
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
            'status_id' => 1,
        ]);

        event(new ResourceChanged(
            'crear',
            Ficha::class,
            $ficha->id,
            Auth::id(),
            'Ficha'
        ));

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

        if (!$ficha) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];
        }

        $fichaData = [];

        if (array_key_exists('gestor_id', $data)) $fichaData['gestor_id'] = $data['gestor_id'];
        if (array_key_exists('ficha_number', $data)) $fichaData['ficha_number'] = $data['ficha_number'];
        if (array_key_exists('start_date', $data)) $fichaData['start_date'] = $data['start_date'];
        if (array_key_exists('end_date', $data)) $fichaData['end_date'] = $data['end_date'];
        if (array_key_exists('training_program_id', $data)) $fichaData['training_program_id'] = $data['training_program_id'];
        if (array_key_exists('status_id', $data)) $fichaData['status_id'] = $data['status_id'];

        if (empty($fichaData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $ficha->update($fichaData);

        event(new ResourceChanged(
            'actualizar',
            Ficha::class,
            $ficha->id,
            Auth::id(),
            'Ficha'
        ));

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

        if (!$ficha) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];
        }

        $ficha->delete();

        event(new ResourceChanged(
            'eliminar',
            Ficha::class,
            $id,
            Auth::id(),
            'Ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha eliminada con éxito",
        ];
    }
}