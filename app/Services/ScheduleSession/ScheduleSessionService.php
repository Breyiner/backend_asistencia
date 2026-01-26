<?php

namespace App\Services\ScheduleSession;

use App\Events\ResourceChanged;
use App\Models\Ficha;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use Illuminate\Support\Facades\Auth;

class ScheduleSessionService
{
    public function getAll(): array
    {
        $sessions = ScheduleSession::with(['instructor', 'schedule', 'shift', 'classroom', 'day'])
            ->orderBy('day_id')
            ->orderBy('start_time')
            ->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registros del horario obtenidos correctamente',
            'data' => $sessions
        ];
    }

    public function getById(int $id): array
    {
        $session = ScheduleSession::with(['instructor', 'schedule', 'shift', 'classroom', 'day'])->find($id);

        if (!$session) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Registro no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registro obtenido correctamente',
            'data' => $session
        ];
    }

    public function getByFichaId($fichaId)
    {
        $userId = Auth::id();
        $user   = Auth::user();

        $isAdmin = $user?->hasRole('Administrador') ?? false;

        $ficha = Ficha::select(['id', 'gestor_id'])
            ->with(['currentFichaTerm:id,ficha_id,is_current'])
            ->find($fichaId);

        if (!$ficha || !$ficha->currentFichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'La ficha no tiene trimestre actual (ficha_term actual).',
            ];
        }

        $fichaTermId = $ficha->currentFichaTerm->id;

        $isGestorOfFicha = (int) ($ficha->gestor_id ?? 0) === (int) $userId;

        if (!$isAdmin && !$isGestorOfFicha) {
            $hasAnySession = ScheduleSession::whereHas('schedule', function ($q) use ($fichaTermId) {
                $q->where('ficha_term_id', $fichaTermId);
            })
                ->where('instructor_id', $userId)
                ->exists();

            if (!$hasAnySession) {
                return [
                    'error' => true,
                    'code' => 403,
                    'message' => 'No tienes acceso al horario de esta ficha.',
                ];
            }
        }

        $schedule = Schedule::select(['id', 'ficha_term_id'])
            ->where('ficha_term_id', $fichaTermId)
            ->with([
                'scheduleSessions' => function ($q) use ($isAdmin, $isGestorOfFicha, $userId) {
                    $q->select(['id', 'schedule_id', 'day_id', 'shift_id', 'instructor_id', 'start_time', 'end_time'])
                        ->when(!$isAdmin && !$isGestorOfFicha, function ($qq) use ($userId) {
                            $qq->where('instructor_id', $userId);
                        })
                        ->orderBy('day_id')
                        ->orderBy('start_time');
                },
                'scheduleSessions.day:id,name',
                'scheduleSessions.shift:id,name',
                'scheduleSessions.instructor:id',
                'scheduleSessions.instructor.profile:id,user_id,first_name,last_name',
            ])
            ->first();

        if (!$schedule) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'No hay horario para el trimestre actual de la ficha.',
            ];
        }

        $sessions = $schedule->scheduleSessions
            ->map(function ($s) {
                $dayName = $s->day?->name ?? 'Sin día';
                $shiftName = $s->shift?->name ?? 'Sin jornada';

                $start = $s->start_time ? substr($s->start_time, 0, 5) : '--:--';
                $end   = $s->end_time ? substr($s->end_time, 0, 5) : '--:--';

                $first = $s->instructor?->profile?->first_name ?? '';
                $last  = $s->instructor?->profile?->last_name ?? '';
                $instructorName = trim("$first $last");
                if ($instructorName === '') $instructorName = 'Sin instructor';

                return [
                    'id' => $s->id,
                    'name' => "{$dayName} - {$shiftName} - {$start} - {$end} - {$instructorName}",
                ];
            })
            ->values();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Sesiones del horario actual obtenidas con éxito',
            'data' => $sessions,
        ];
    }

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
            'error' => false,
            'code' => 201,
            'message' => 'Registro creado correctamente',
            'data' => $session,
        ];
    }

    public function update(array $data, int $id): array
    {
        $session = ScheduleSession::find($id);

        if (!$session) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Registro no encontrado',
            ];
        }

        $sessionData = [];

        if (array_key_exists('instructor_id', $data)) $sessionData['instructor_id'] = $data['instructor_id'];
        if (array_key_exists('shift_id', $data))      $sessionData['shift_id'] = $data['shift_id'];
        if (array_key_exists('classroom_id', $data))  $sessionData['classroom_id'] = $data['classroom_id'];
        if (array_key_exists('day_id', $data))        $sessionData['day_id'] = $data['day_id'];
        if (array_key_exists('start_time', $data))    $sessionData['start_time'] = $data['start_time'];
        if (array_key_exists('end_time', $data))      $sessionData['end_time'] = $data['end_time'];

        if (empty($sessionData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
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
            'error' => false,
            'code' => 200,
            'message' => 'Registro actualizado correctamente',
            'data' => $session->fresh(),
        ];
    }

    public function delete(int $id): array
    {
        $session = ScheduleSession::find($id);

        if (!$session) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Registro no encontrado',
            ];
        }

        $session->delete();

        event(new ResourceChanged(
            'eliminar',
            ScheduleSession::class,
            $id,
            Auth::id(),
            'Sesión de horario'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registro eliminado correctamente',
        ];
    }
}
