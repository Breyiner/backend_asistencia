<?php

namespace App\Services\Schedule;

use App\Events\ResourceChanged;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;

class ScheduleService
{
    public function getAll()
    {
        $schedules = Schedule::with('fichaTerm')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Horarios obtenidos correctamente',
            'data' => $schedules
        ];
    }

    public function getById($id): array
    {
        $schedule = Schedule::query()
            ->select(['id', 'description', 'ficha_term_id'])
            ->with([
                'fichaTerm:id,ficha_id,term_id,phase_id,start_date,end_date',
                'fichaTerm.ficha:id,ficha_number,training_program_id',
                'fichaTerm.ficha.trainingProgram:id,name',
                'fichaTerm.term:id,name',
                'fichaTerm.phase:id,name',

                'scheduleSessions:id,schedule_id,instructor_id,shift_id,classroom_id,day_id,start_time,end_time',
                'scheduleSessions.day:id,name',
                'scheduleSessions.shift:id,name',
                'scheduleSessions.classroom:id,name',
                'scheduleSessions.instructor:id',
                'scheduleSessions.instructor.profile:id,user_id,first_name,last_name',
            ])
            ->find($id);

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Horario no encontrado'
            ];
        }

        $ficha = $schedule->fichaTerm?->ficha;
        $term  = $schedule->fichaTerm?->term;
        $phase = $schedule->fichaTerm?->phase;

        $data = [
            'id' => $schedule->id,

            'ficha' => [
                'id' => $ficha?->id,
                'number' => $ficha?->ficha_number,
                'training_program_name' => $ficha?->trainingProgram?->name,
            ],

            'term' => [
                'id' => $term?->id,
                'name' => $term?->name,
            ],

            'phase' => [
                'id' => $phase?->id,
                'name' => $phase?->name,
            ],

            'term_dates' => [
                'start_date' => $schedule->fichaTerm?->start_date?->toDateString(),
                'end_date' => $schedule->fichaTerm?->end_date?->toDateString(),
            ],

            'sessions' => $schedule->scheduleSessions->map(function ($s) {
                $first = $s->instructor?->profile?->first_name ?? '';
                $last  = $s->instructor?->profile?->last_name ?? '';

                return [
                    'id' => $s->id,

                    'day' => [
                        'id' => $s->day_id,
                        'name' => $s->day?->name,
                    ],

                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,

                    'shift' => [
                        'id' => $s->shift_id,
                        'name' => $s->shift?->name,
                    ],

                    'instructor' => [
                        'id' => $s->instructor_id,
                        'first_name' => $first,
                        'last_name' => $last,
                        'full_name' => trim("$first $last"),
                    ],

                    'classroom' => [
                        'id' => $s->classroom_id,
                        'name' => $s->classroom?->name,
                    ],
                ];
            })->values(),
        ];

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Horario obtenido con éxito',
            'data' => $data
        ];
    }

    public function getByFichaTermId($fichaTermId): array
    {
        $schedule = Schedule::query()
            ->select(['id', 'description', 'ficha_term_id', 'updated_at', 'created_at'])
            ->with([
                'fichaTerm:id,ficha_id,term_id,phase_id,start_date,end_date',
                'fichaTerm.ficha:id,ficha_number,training_program_id',
                'fichaTerm.ficha.trainingProgram:id,name',
                'fichaTerm.term:id,name',
                'fichaTerm.phase:id,name',

                'scheduleSessions' => function ($q) {
                    $q->select([
                        'id',
                        'schedule_id',
                        'instructor_id',
                        'shift_id',
                        'classroom_id',
                        'day_id',
                        'start_time',
                        'end_time',
                    ])
                        ->orderBy('day_id', 'asc')
                        ->orderBy('shift_id', 'asc');
                },
                'scheduleSessions.day:id,name',
                'scheduleSessions.shift:id,name',
                'scheduleSessions.classroom:id,name',
                'scheduleSessions.instructor:id',
                'scheduleSessions.instructor.profile:id,user_id,first_name,last_name',
            ])
            ->where('ficha_term_id', $fichaTermId)
            ->first();

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Horario no encontrado'
            ];
        }

        $ficha = $schedule->fichaTerm?->ficha;
        $term  = $schedule->fichaTerm?->term;
        $phase = $schedule->fichaTerm?->phase;

        $data = [
            'id' => $schedule->id,

            'ficha' => [
                'id' => $ficha?->id,
                'number' => $ficha?->ficha_number,
                'training_program_name' => $ficha?->trainingProgram?->name,
            ],

            'term' => [
                'id' => $term?->id,
                'name' => $term?->name,
            ],

            'phase' => [
                'id' => $phase?->id,
                'name' => $phase?->name,
            ],

            'term_dates' => [
                'start_date' => $schedule->fichaTerm?->start_date?->toDateString(),
                'end_date' => $schedule->fichaTerm?->end_date?->toDateString(),
            ],

            'updated_at' => $schedule->updated_at?->toDateString(),
            'created_at' => $schedule->created_at?->toDateString(),

            'sessions' => $schedule->scheduleSessions->map(function ($s) {
                $first = $s->instructor?->profile?->first_name ?? '';
                $last  = $s->instructor?->profile?->last_name ?? '';

                return [
                    'id' => $s->id,

                    'day' => [
                        'id' => $s->day_id,
                        'name' => $s->day?->name,
                    ],

                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,

                    'shift' => [
                        'id' => $s->shift_id,
                        'name' => $s->shift?->name,
                    ],

                    'instructor' => [
                        'id' => $s->instructor_id,
                        'first_name' => $first,
                        'last_name' => $last,
                        'full_name' => trim("$first $last"),
                    ],

                    'classroom' => [
                        'id' => $s->classroom_id,
                        'name' => $s->classroom?->name,
                    ],
                ];
            })->values(),
        ];

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Horario obtenido con éxito',
            'data' => $data
        ];
    }


    public function create(array $data): array
    {
        $schedule = Schedule::create([
            'description' => $data['description'] ?? null,
            'ficha_term_id' => $data['ficha_term_id'],
        ]);

        event(new ResourceChanged(
            'crear',
            Schedule::class,
            $schedule->id,
            Auth::id(),
            'Horario'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Horario creado correctamente',
            'data' => $schedule
        ];
    }

    public function update($data, $id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Horario no encontrado'
            ];
        }

        $scheduleData = [];

        if (array_key_exists('description', $data)) {
            $scheduleData['description'] = $data['description'];
        }

        if (empty($scheduleData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $schedule->update($scheduleData);

        event(new ResourceChanged(
            'actualizar',
            Schedule::class,
            $schedule->id,
            Auth::id(),
            'Horario'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Trimestre de la ficha actualizado con éxito",
            "data" => $schedule->fresh()
        ];
    }

    public function delete(int $id): array
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Horario no encontrado'
            ];
        }

        $schedule->delete();

        event(new ResourceChanged(
            'eliminar',
            Schedule::class,
            $id,
            Auth::id(),
            'Horario'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Horario eliminado correctamente'
        ];
    }
}
