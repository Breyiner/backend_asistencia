<?php

namespace App\Services\ScheduleSession;

use App\Events\ResourceChanged;
use App\Models\Ficha;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de sesiones de horario.
 *
 * Una ScheduleSession representa un bloque de clase recurrente dentro de un horario:
 * día de la semana, franja horaria, instructor y aula asignados. Pertenece a un
 * Schedule que a su vez pertenece a un FichaTerm.
 *
 * El método getByFichaId() aplica lógica de visibilidad propia (sin acting_role_code):
 * ADMIN y GESTOR ven todas las sesiones del horario; el INSTRUCTOR solo ve las suyas.
 */
class ScheduleSessionService
{
    /**
     * Retorna todas las sesiones de horario ordenadas por día y hora.
     *
     * Uso administrativo: sin filtros ni paginación. Carga todas las relaciones
     * necesarias para el listado general. Si el volumen crece, considerar paginar.
     *
     * @return array
     */
    public function getAll(): array
    {
        // with() sin select() limitado: uso interno donde se necesitan todos los campos.
        $sessions = ScheduleSession::with(['instructor', 'schedule', 'timeSlot', 'classroom', 'day'])
            ->orderBy('day_id')
            ->orderBy('start_time')
            ->get();

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Registros del horario obtenidos correctamente',
            'data'    => $sessions,
        ];
    }

    /**
     * Retorna el detalle de una sesión de horario por su ID.
     *
     * @param  int  $id  ID de la sesión.
     * @return array
     */
    public function getById(int $id): array
    {
        $session = ScheduleSession::with(['instructor', 'schedule', 'timeSlot', 'classroom', 'day'])
            ->find($id);

        if (!$session) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Registro no encontrado',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Registro obtenido correctamente',
            'data'    => $session,
        ];
    }

    /**
     * Retorna las sesiones del trimestre activo de una ficha para el select de RealClass.
     *
     * Aplica lógica de visibilidad propia (independiente de acting_role_code):
     * - ADMIN: ve todas las sesiones del horario.
     * - GESTOR de la ficha: ve todas las sesiones del horario.
     * - INSTRUCTOR: solo ve las sesiones donde él está asignado como instructor.
     * - Cualquier otro: 403 si no tiene sesiones en el horario de la ficha.
     *
     * El resultado es una lista de labels descriptivos ('Lunes - Mañana - 07:00 - 09:00 - Juan Pérez')
     * para poblar el select de schedule_session_id al registrar una clase real.
     *
     * @param  mixed  $fichaId  ID de la ficha.
     * @return array
     */
    public function getByFichaId($fichaId)
    {
        $userId = Auth::id();
        $user   = Auth::user();

        // hasRole() de Spatie: verifica si el usuario tiene el rol 'Administrador'.
        $isAdmin = $user?->hasRole('Administrador') ?? false;

        // Carga solo los campos necesarios de la ficha y su término actual.
        $ficha = Ficha::select(['id', 'gestor_id'])
            ->with(['currentFichaTerm:id,ficha_id,is_current'])
            ->find($fichaId);

        // Si la ficha no existe o no tiene término activo, no hay horario que mostrar.
        if (!$ficha || !$ficha->currentFichaTerm) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'La ficha no tiene trimestre actual (ficha_term actual).',
                'data'    => [],
            ];
        }

        $fichaTermId = $ficha->currentFichaTerm->id;

        // El gestor de la ficha tiene visibilidad total igual que el ADMIN.
        $isGestorOfFicha = (int) ($ficha->gestor_id ?? 0) === (int) $userId;

        // Si no es ADMIN ni GESTOR, verifica que el usuario tenga al menos una sesión en el horario.
        // Evita exponer el horario de fichas a instructores que no están asignados.
        if (!$isAdmin && !$isGestorOfFicha) {
            $hasAnySession = ScheduleSession::whereHas('schedule', function ($q) use ($fichaTermId) {
                $q->where('ficha_term_id', $fichaTermId);
            })
                ->where('instructor_id', $userId)
                ->exists();

            // exists() es eficiente: para en el primer resultado encontrado.
            if (!$hasAnySession) {
                return [
                    'error'   => true,
                    'code'    => 403,
                    'message' => 'No tienes acceso al horario de esta ficha.',
                    'data'    => [],
                ];
            }
        }

        $schedule = Schedule::select(['id', 'ficha_term_id'])
            ->where('ficha_term_id', $fichaTermId)
            ->with([
                // Closure: permite filtrar sesiones por instructor dentro del eager loading.
                'scheduleSessions' => function ($q) use ($isAdmin, $isGestorOfFicha, $userId) {
                    $q->select([
                        'id',
                        'schedule_id',
                        'day_id',
                        'time_slot_id',
                        'instructor_id',
                        'start_time',
                        'end_time',
                    ])
                    // when(): aplica el filtro de instructor solo si no es ADMIN ni GESTOR.
                    ->when(!$isAdmin && !$isGestorOfFicha, function ($qq) use ($userId) {
                        $qq->where('instructor_id', $userId);
                    })
                    ->orderBy('day_id')
                    ->orderBy('start_time');
                },

                'scheduleSessions.day:id,name',
                'scheduleSessions.timeSlot:id,name,code,start_time,end_time',
                'scheduleSessions.instructor:id',
                'scheduleSessions.instructor.profile:id,user_id,first_name,last_name',
            ])
            ->first();

        // El horario podría no existir aunque el FichaTerm sí: aún no fue creado.
        if (!$schedule) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'No hay horario para el trimestre actual de la ficha.',
                'data'    => [],
            ];
        }

        // Mapea cada sesión a un label descriptivo para el select del formulario de RealClass.
        $sessions = $schedule->scheduleSessions
            ->map(function ($s) {
                $dayName      = $s->day?->name      ?? 'Sin día';
                $timeSlotName = $s->timeSlot?->name ?? 'Sin franja';

                // substr(..., 0, 5): recorta 'HH:MM:SS' a 'HH:MM' para display.
                $start = $s->start_time ? substr($s->start_time, 0, 5) : '--:--';
                $end   = $s->end_time   ? substr($s->end_time, 0, 5)   : '--:--';

                $first = $s->instructor?->profile?->first_name ?? '';
                $last  = $s->instructor?->profile?->last_name  ?? '';
                // Fallback explícito si el nombre queda vacío tras el trim.
                $instructorName = trim("$first $last") ?: 'Sin instructor';

                return [
                    'id' => $s->id,
                    // Label completo para mostrar en el select de schedule_session_id.
                    'name' => "{$dayName} - {$timeSlotName} - {$start} - {$end} - {$instructorName}",
                ];
            })
            ->values();

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Sesiones del horario actual obtenidas con éxito',
            'data'    => $sessions,
        ];
    }

    /**
     * Crea una nueva sesión de horario.
     *
     * Pasa $data directamente al create(): la validación de campos
     * se delega completamente al StoreScheduleSessionRequest.
     *
     * @param  array  $data  Datos validados (schedule_id, day_id, time_slot_id, instructor_id, etc.).
     * @return array
     */
    public function create(array $data): array
    {
        $session = ScheduleSession::create($data);

        event(new ResourceChanged(
            'crear',
            ScheduleSession::class,
            $session->id,
            Auth::id(),
            'Sesión de horario'
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Registro creado correctamente',
            'data'    => $session,
        ];
    }

    /**
     * Actualiza una sesión de horario existente.
     *
     * Solo actualiza los campos presentes en $data. El evento se dispara
     * únicamente si hay campos que efectivamente cambiar.
     *
     * @param  array  $data  Campos a actualizar (instructor_id, time_slot_id, classroom_id, day_id, start_time, end_time).
     * @param  int    $id    ID de la sesión.
     * @return array
     */
    public function update(array $data, int $id): array
    {
        $session = ScheduleSession::find($id);

        if (!$session) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Registro no encontrado',
                'data'    => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $sessionData = [];

        if (array_key_exists('instructor_id', $data)) $sessionData['instructor_id'] = $data['instructor_id'];
        if (array_key_exists('time_slot_id', $data))  $sessionData['time_slot_id']  = $data['time_slot_id'];
        if (array_key_exists('classroom_id', $data))  $sessionData['classroom_id']  = $data['classroom_id'];
        if (array_key_exists('day_id', $data))        $sessionData['day_id']        = $data['day_id'];
        if (array_key_exists('start_time', $data))    $sessionData['start_time']    = $data['start_time'];
        if (array_key_exists('end_time', $data))      $sessionData['end_time']      = $data['end_time'];

        if (empty($sessionData)) {
            return [
                'error'   => true,
                'code'    => 400,
                'message' => 'No hay datos para actualizar',
                'data'    => [],
            ];
        }

        $session->update($sessionData);

        event(new ResourceChanged(
            'actualizar',
            ScheduleSession::class,
            $session->id,
            Auth::id(),
            'Sesión de horario'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Registro actualizado correctamente',
            // fresh() recarga desde BD para devolver los datos ya persistidos.
            'data'    => $session->fresh(),
        ];
    }

    /**
     * Elimina una sesión de horario por su ID.
     *
     * ⚠️ Precaución: eliminar una sesión que tenga RealClasses asociadas puede
     * dejar clases reales sin schedule_session_id si no hay FK con SET NULL.
     *
     * @param  int  $id  ID de la sesión.
     * @return array
     */
    public function delete(int $id): array
    {
        $session = ScheduleSession::find($id);

        if (!$session) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Registro no encontrado',
                'data'    => [],
            ];
        }

        $session->delete();

        // Nota: se usa $session->id (no $id) para consistencia con el patrón del sistema.
        event(new ResourceChanged(
            'eliminar',
            ScheduleSession::class,
            $session->id,
            Auth::id(),
            'Sesión de horario'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Registro eliminado correctamente',
            'data'    => [],
        ];
    }
}
