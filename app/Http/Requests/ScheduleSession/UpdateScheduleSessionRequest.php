<?php

namespace App\Http\Requests\ScheduleSession;

use App\Models\ScheduleSession;
use App\Models\TimeSlot;
use App\Models\Schedule;
use App\Rules\UserHasRoleCode;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** de sesión de horario.
 *
 * Igual que Store pero soporta campos opcionales (`sometimes`) y excluye
 * la sesión actual en chequeos de solapamiento.
 */
class UpdateScheduleSessionRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene ID de la sesión actual desde parámetros de ruta.
     */
    private function currentScheduleSessionId(): ?int
    {
        $param = $this->route('schedule_session_id');
        return $param ? (int) $param : null;
    }

    /**
     * Reglas de validación (UPDATE).
     * 
     * Campos opcionales con `sometimes|required` (solo valida si presente).
     */
    public function rules(): array
    {
        return [
            'instructor_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:users,id',
                new UserHasRoleCode(roleCode: 'INSTRUCTOR'),
            ],
            'schedule_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:schedules,id',
            ],
            'time_slot_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:time_slots,id',
            ],
            'classroom_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:classrooms,id',
            ],
            'day_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:days,id',
            ],
            'start_time' => [
                'sometimes',
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'sometimes',
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
        ];
    }

    /**
     * Validaciones **COMPLEJAS** (misma lógica que Store pero con UPDATE).
     * 
     * **Diferencias clave:**
     * - Usa valores actuales si no se envían nuevos
     * - Excluye sesión actual en solapamientos (`where('id', '!=', $currentId)`)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Salir si hay errores previos
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $currentId = $this->currentScheduleSessionId();
            $currentSession = $currentId ? ScheduleSession::find($currentId) : null;

            // **VALORES: nuevos o actuales**
            $scheduleId = $this->schedule_id ?? $currentSession?->schedule_id;
            $timeSlotId = $this->time_slot_id ?? $currentSession?->time_slot_id;
            $dayId = $this->day_id ?? $currentSession?->day_id;
            $classroomId = $this->classroom_id ?? $currentSession?->classroom_id;
            $instructorId = $this->instructor_id ?? $currentSession?->instructor_id;
            $start = $this->start_time ?? $currentSession?->start_time;
            $end = $this->end_time ?? $currentSession?->end_time;

            if (!$start || !$end || !$timeSlotId) {
                return;
            }

            // **1. HORARIOS DENTRO DE FRANJA** (igual que Store)
            $timeSlot = TimeSlot::find($timeSlotId);
            if ($timeSlot) {
                $timeSlotStart = substr($timeSlot->start_time, 0, 5);
                $timeSlotEnd = substr($timeSlot->end_time, 0, 5);

                if ($timeSlotEnd >= $timeSlotStart) {
                    if ($start < $timeSlotStart || $start > $timeSlotEnd) {
                        $validator->errors()->add('start_time', 'La hora de inicio debe estar dentro del rango de la franja horaria.');
                        return;
                    }
                    if ($end < $timeSlotStart || $end > $timeSlotEnd) {
                        $validator->errors()->add('end_time', 'La hora de finalización debe estar dentro del rango de la franja horaria.');
                        return;
                    }
                }
            }

            // **2. COMPATIBILIDAD FRANJA/JORNADA** (igual que Store)
            if ($scheduleId) {
                $schedule = Schedule::with('fichaTerm.ficha:id,shift_id')->find($scheduleId);
                if ($schedule && $schedule->fichaTerm && $schedule->fichaTerm->ficha) {
                    $fichaShiftId = $schedule->fichaTerm->ficha->shift_id;
                    $timeSlot = $timeSlot ?? TimeSlot::find($timeSlotId); // Reutilizar

                    if ($fichaShiftId == 1) {
                        $allowedCodes = ['MORNING', 'AFTERNOON'];
                        if (!in_array($timeSlot?->code, $allowedCodes, true)) {
                            $validator->errors()->add(
                                'time_slot_id',
                                'La franja horaria seleccionada no es compatible con la jornada Diurna de la ficha. Solo se permiten franjas de Mañana o Tarde.'
                            );
                        }
                    } elseif ($fichaShiftId == 2) {
                        $allowedCodes = ['AFTERNOON', 'NIGHT'];
                        if (!in_array($timeSlot?->code, $allowedCodes, true)) {
                            $validator->errors()->add(
                                'time_slot_id',
                                'La franja horaria seleccionada no es compatible con la jornada Nocturna de la ficha. Solo se permiten franjas de Tarde o Noche.'
                            );
                        }
                    }
                }
            }

            // **3. SOLAPAMIENTO AMBIENTE** (excluye sesión actual)
            if ($scheduleId && $dayId && $timeSlotId && $classroomId) {
                $classroomOverlap = ScheduleSession::query()
                    ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
                    ->where('schedule_id', $scheduleId)
                    ->where('day_id', $dayId)
                    ->where('time_slot_id', $timeSlotId)
                    ->where('classroom_id', $classroomId)
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->exists();

                if ($classroomOverlap) {
                    $validator->errors()->add(
                        'classroom_id',
                        'El ambiente ya tiene una clase asignada en ese día y franja horaria que se cruza con el horario ingresado.'
                    );
                }
            }

            // **4. SOLAPAMIENTO INSTRUCTOR** (excluye sesión actual)
            if ($scheduleId && $dayId && $timeSlotId && $instructorId) {
                $instructorOverlap = ScheduleSession::query()
                    ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
                    ->where('schedule_id', $scheduleId)
                    ->where('day_id', $dayId)
                    ->where('time_slot_id', $timeSlotId)
                    ->where('instructor_id', $instructorId)
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->exists();

                if ($instructorOverlap) {
                    $validator->errors()->add(
                        'instructor_id',
                        'El instructor ya tiene una clase asignada en ese día y franja horaria que se cruza con el horario ingresado.'
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados (igual que Store).
     */
    public function messages(): array
    {
        return [
            // Mismos mensajes que StoreScheduleSessionRequest
            'instructor_id.required' => 'El :attribute es obligatorio.',
            'instructor_id.integer' => 'El :attribute debe ser un número.',
            'instructor_id.exists' => 'El :attribute seleccionado no existe.',
            // ... resto igual
        ];
    }

    /**
     * Atributos legibles (igual que Store).
     */
    public function attributes(): array
    {
        return [
            'instructor_id' => 'instructor',
            'schedule_id' => 'horario',
            'time_slot_id' => 'franja horaria',
            'classroom_id' => 'ambiente',
            'day_id' => 'día',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
        ];
    }
}
