<?php

namespace App\Services\Schedule;

use App\Events\ResourceChanged;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de horarios.
 *
 * Un horario (Schedule) pertenece a un FichaTerm (relación 1:1) y agrupa
 * las sesiones de clase (ScheduleSessions) con su día, franja horaria,
 * instructor y aula asignados.
 *
 * Métodos de consulta:
 * - getAll():             Listado general sin relaciones detalladas (uso administrativo).
 * - getById():            Detalle por ID con sesiones completas.
 * - getByFichaTermId():   Detalle por ficha_term_id con sesiones ordenadas (uso principal del frontend).
 */
class ScheduleService
{
    /**
     * Retorna todos los horarios con su FichaTerm asociado.
     *
     * Uso administrativo: no aplica filtros ni paginación porque se usa
     * como listado de referencia interno. Si el volumen crece, considerar paginar.
     *
     * @return array
     */
    public function getAll()
    {
        // with('fichaTerm'): carga la relación para contexto básico sin anidar más relaciones.
        $schedules = Schedule::with('fichaTerm')->get();

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Horarios obtenidos correctamente',
            'data'    => $schedules,
        ];
    }

    /**
     * Retorna el detalle completo de un horario por su ID.
     *
     * Incluye la cadena completa de relaciones hasta el programa de formación
     * y todas las sesiones con instructor, aula y franja horaria.
     * Las horas de sesión se recortan a 'HH:MM' con substr(..., 0, 5).
     *
     * @param  mixed  $id  ID del horario.
     * @return array
     */
    public function getById($id): array
    {
        $schedule = Schedule::query()
            ->select(['id', 'description', 'ficha_term_id'])
            ->with([
                // Cadena de relaciones para contexto completo de la ficha.
                'fichaTerm:id,ficha_id,term_id,phase_id,start_date,end_date',
                'fichaTerm.ficha:id,ficha_number,training_program_id',
                'fichaTerm.ficha.trainingProgram:id,name',
                'fichaTerm.term:id,name',
                'fichaTerm.phase:id,name',

                // Sesiones con todas sus relaciones para la vista de detalle.
                'scheduleSessions:id,schedule_id,instructor_id,time_slot_id,classroom_id,day_id,start_time,end_time',
                'scheduleSessions.day:id,name',
                'scheduleSessions.timeSlot:id,name,code,start_time,end_time',
                'scheduleSessions.classroom:id,name',
                'scheduleSessions.instructor:id',
                'scheduleSessions.instructor.profile:id,user_id,first_name,last_name',
            ])
            ->find($id);

        if (!$schedule) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Horario no encontrado',
                'data'    => [],
            ];
        }

        // Extracciones locales para legibilidad en el array de respuesta.
        $ficha = $schedule->fichaTerm?->ficha;
        $term  = $schedule->fichaTerm?->term;
        $phase = $schedule->fichaTerm?->phase;

        $data = [
            'id' => $schedule->id,

            'ficha' => [
                'id'                    => $ficha?->id,
                'number'                => $ficha?->ficha_number,
                'training_program_name' => $ficha?->trainingProgram?->name,
            ],

            'term' => [
                'id'   => $term?->id,
                'name' => $term?->name,
            ],

            'phase' => [
                'id'   => $phase?->id,
                'name' => $phase?->name,
            ],

            'term_dates' => [
                'start_date' => $schedule->fichaTerm?->start_date?->toDateString(),
                'end_date'   => $schedule->fichaTerm?->end_date?->toDateString(),
            ],

            // Sesiones mapeadas a array plano con objetos anidados por entidad.
            'sessions' => $schedule->scheduleSessions->map(function ($s) {
                $first = $s->instructor?->profile?->first_name ?? '';
                $last  = $s->instructor?->profile?->last_name  ?? '';

                return [
                    'id' => $s->id,

                    'day' => [
                        'id'   => $s->day_id,
                        'name' => $s->day?->name,
                    ],

                    // substr(..., 0, 5): recorta 'HH:MM:SS' a 'HH:MM' para display.
                    'start_time' => $s->start_time ? substr($s->start_time, 0, 5) : null,
                    'end_time'   => $s->end_time   ? substr($s->end_time, 0, 5)   : null,

                    'time_slot' => [
                        'id'         => $s->time_slot_id,
                        'name'       => $s->timeSlot?->name,
                        'code'       => $s->timeSlot?->code,
                        'start_time' => $s->timeSlot?->start_time ? substr($s->timeSlot->start_time, 0, 5) : null,
                        'end_time'   => $s->timeSlot?->end_time   ? substr($s->timeSlot->end_time, 0, 5)   : null,
                    ],

                    'instructor' => [
                        'id'         => $s->instructor_id,
                        'first_name' => $first,
                        'last_name'  => $last,
                        'full_name'  => trim("$first $last"),
                    ],

                    'classroom' => [
                        'id'   => $s->classroom_id,
                        'name' => $s->classroom?->name,
                    ],
                ];
            })->values(),
        ];

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Horario obtenido con éxito',
            'data'    => $data,
        ];
    }

    /**
     * Retorna el horario de un FichaTerm específico con sesiones ordenadas.
     *
     * Es el método principal que usa el frontend para cargar el horario de una ficha.
     * A diferencia de getById(), las sesiones se ordenan por day_id + time_slot_id
     * para renderizar la tabla de horario en orden cronológico.
     *
     * @param  mixed  $fichaTermId  ID del FichaTerm cuyo horario se quiere obtener.
     * @return array
     */
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

                // Closure en lugar de string: permite agregar orderBy a la relación de sesiones.
                'scheduleSessions' => function ($q) {
                    $q->select([
                        'id',
                        'schedule_id',
                        'instructor_id',
                        'time_slot_id',
                        'classroom_id',
                        'day_id',
                        'start_time',
                        'end_time',
                    ])
                        // Ordenado por día y luego por franja: garantiza orden cronológico en la tabla de horario.
                        ->orderBy('day_id', 'asc')
                        ->orderBy('time_slot_id', 'asc');
                },

                'scheduleSessions.day:id,name',
                'scheduleSessions.timeSlot:id,name,code,start_time,end_time',
                'scheduleSessions.classroom:id,name',
                'scheduleSessions.instructor:id',
                'scheduleSessions.instructor.profile:id,user_id,first_name,last_name',
            ])
            // where + first(): un FichaTerm tiene exactamente un Schedule (relación 1:1).
            ->where('ficha_term_id', $fichaTermId)
            ->first();

        if (!$schedule) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Horario no encontrado',
                'data'    => [],
            ];
        }

        $ficha = $schedule->fichaTerm?->ficha;
        $term  = $schedule->fichaTerm?->term;
        $phase = $schedule->fichaTerm?->phase;

        $data = [
            'id' => $schedule->id,

            'ficha' => [
                'id'                    => $ficha?->id,
                'number'                => $ficha?->ficha_number,
                'training_program_name' => $ficha?->trainingProgram?->name,
            ],

            'term' => [
                'id'   => $term?->id,
                'name' => $term?->name,
            ],

            'phase' => [
                'id'   => $phase?->id,
                'name' => $phase?->name,
            ],

            'term_dates' => [
                'start_date' => $schedule->fichaTerm?->start_date?->toDateString(),
                'end_date'   => $schedule->fichaTerm?->end_date?->toDateString(),
            ],

            'updated_at' => $schedule->updated_at?->toDateString(),
            'created_at' => $schedule->created_at?->toDateString(),

            'sessions' => $schedule->scheduleSessions->map(function ($s) {
                $first = $s->instructor?->profile?->first_name ?? '';
                $last  = $s->instructor?->profile?->last_name  ?? '';

                return [
                    'id' => $s->id,

                    'day' => [
                        'id'   => $s->day_id,
                        'name' => $s->day?->name,
                    ],

                    // Nota: aquí start_time y end_time se devuelven sin recortar (HH:MM:SS).
                    // Si el frontend necesita 'HH:MM', aplicar substr(..., 0, 5) igual que en getById().
                    'start_time' => $s->start_time,
                    'end_time'   => $s->end_time,

                    'time_slot' => [
                        'id'         => $s->time_slot_id,
                        'name'       => $s->timeSlot?->name,
                        'code'       => $s->timeSlot?->code,
                        'start_time' => $s->timeSlot?->start_time ? substr($s->timeSlot->start_time, 0, 5) : null,
                        'end_time'   => $s->timeSlot?->end_time   ? substr($s->timeSlot->end_time, 0, 5)   : null,
                    ],

                    'instructor' => [
                        'id'         => $s->instructor_id,
                        'first_name' => $first,
                        'last_name'  => $last,
                        'full_name'  => trim("$first $last"),
                    ],

                    'classroom' => [
                        'id'   => $s->classroom_id,
                        'name' => $s->classroom?->name,
                    ],
                ];
            })->values(),
        ];

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Horario obtenido con éxito',
            'data'    => $data,
        ];
    }

    /**
     * Crea un nuevo horario asociado a un FichaTerm.
     *
     * Solo se puede crear un horario por FichaTerm (relación 1:1).
     * La validación de unicidad de ficha_term_id se delega al Request.
     *
     * @param  array  $data  Datos validados (ficha_term_id requerido, description opcional).
     * @return array
     */
    public function create(array $data): array
    {
        $schedule = Schedule::create([
            'description'   => $data['description'] ?? null,
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
            'error'   => false,
            'code'    => 201,
            'message' => 'Horario creado correctamente',
            'data'    => $schedule,
        ];
    }

    /**
     * Actualiza un horario existente.
     *
     * Solo permite actualizar 'description': ficha_term_id es inmutable tras la creación
     * porque cambiar la ficha del horario invalidaría todas las sesiones asociadas.
     *
     * @param  array  $data  Campos a actualizar (solo description).
     * @param  mixed  $id    ID del horario.
     * @return array
     */
    public function update($data, $id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Horario no encontrado',
                'data'    => [],
            ];
        }

        // ficha_term_id se excluye: cambiar la ficha asociada invalidaría todas las sesiones.
        $scheduleData = [];

        if (array_key_exists('description', $data)) $scheduleData['description'] = $data['description'];

        if (empty($scheduleData)) {
            return [
                'error'   => true,
                'code'    => 400,
                'message' => 'No hay datos para actualizar',
                'data'    => [],
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
            'error'   => false,
            'code'    => 200,
            'message' => 'Horario actualizado con éxito',
            // fresh() recarga desde BD para devolver los datos ya persistidos.
            'data'    => $schedule->fresh(),
        ];
    }

    /**
     * Elimina un horario por su ID.
     *
     * Las ScheduleSessions asociadas se eliminan por CASCADE en la migración.
     * Al eliminar el horario se pierde toda la configuración de sesiones de ese FichaTerm.
     *
     * @param  int  $id  ID del horario.
     * @return array
     */
    public function delete(int $id): array
    {
        // 1) Buscar el horario por ID.
        $schedule = Schedule::find($id);

        // 2) Validar existencia para responder 404 controlado.
        if (!$schedule) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Horario no encontrado',
                'data'    => [],
            ];
        }

        // 3) Validar integridad referencial (regla de negocio):
        //    Si el horario tiene sesiones asociadas, no se debe permitir eliminarlo,
        //    porque se perdería la configuración del horario (y si hay CASCADE, se borrarían).
        //    exists() es más eficiente que count(): no cuenta todo, solo verifica 1 registro.
        if ($schedule->scheduleSessions()->exists()) {
            return [
                'error'   => true,
                'code'    => 409, // Conflicto: no se puede eliminar por dependencias existentes.
                'message' => 'No se puede eliminar este horario porque tiene sesiones asociadas',
                'data'    => [],
            ];
        }

        // 4) Guardar el ID antes de eliminar para auditoría/evento.
        $deletedId = $schedule->id;

        // 5) Eliminar solo si no hay dependencias.
        $schedule->delete();

        // 6) Auditoría / notificación.
        event(new ResourceChanged(
            'eliminar',
            Schedule::class,
            $deletedId,
            Auth::id(),
            'Horario'
        ));

        // 7) Respuesta exitosa.
        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Horario eliminado correctamente',
            'data'    => [],
        ];
    }
}
