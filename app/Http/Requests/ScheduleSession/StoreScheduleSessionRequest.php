<?php

namespace App\Http\Requests\ScheduleSession;

use App\Models\ScheduleSession;
use App\Models\TimeSlot;
use App\Models\Schedule;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleSessionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'instructor_id' => [
                'required',
                'integer',
                'exists:users,id',
                new UserHasRole('Instructor'),
            ],

            'schedule_id' => [
                'required',
                'integer',
                'exists:schedules,id',
            ],

            'time_slot_id' => [
                'required',
                'integer',
                'exists:time_slots,id',
            ],

            'classroom_id' => [
                'required',
                'integer',
                'exists:classrooms,id',
            ],

            'day_id' => [
                'required',
                'integer',
                'exists:days,id',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = $this->start_time;
            $end   = $this->end_time;

            $timeSlot = TimeSlot::find($this->time_slot_id);
            if ($timeSlot) {
                $timeSlotStart = substr($timeSlot->start_time, 0, 5);
                $timeSlotEnd   = substr($timeSlot->end_time, 0, 5);

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

            $schedule = Schedule::with('fichaTerm.ficha:id,shift_id')
                ->find($this->schedule_id);

            if ($schedule && $schedule->fichaTerm && $schedule->fichaTerm->ficha) {
                $fichaShiftId = $schedule->fichaTerm->ficha->shift_id;

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

            $classroomOverlap = ScheduleSession::query()
                ->where('schedule_id', $this->schedule_id)
                ->where('day_id', $this->day_id)
                ->where('time_slot_id', $this->time_slot_id)
                ->where('classroom_id', $this->classroom_id)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($classroomOverlap) {
                $validator->errors()->add(
                    'classroom_id',
                    'El ambiente ya tiene una clase asignada en ese día y franja horaria que se cruza con el horario ingresado.'
                );
            }

            $instructorOverlap = ScheduleSession::query()
                ->where('schedule_id', $this->schedule_id)
                ->where('day_id', $this->day_id)
                ->where('time_slot_id', $this->time_slot_id)
                ->where('instructor_id', $this->instructor_id)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($instructorOverlap) {
                $validator->errors()->add(
                    'instructor_id',
                    'El instructor ya tiene una clase asignada en ese día y franja horaria que se cruza con el horario ingresado.'
                );
            }
        });
    }

    public function messages()
    {
        return [
            'instructor_id.required' => 'El :attribute es obligatorio.',
            'instructor_id.integer' => 'El :attribute debe ser un número.',
            'instructor_id.exists' => 'El :attribute seleccionado no existe.',

            'schedule_id.required' => 'El :attribute es obligatorio.',
            'schedule_id.integer' => 'El :attribute debe ser un número.',
            'schedule_id.exists' => 'El :attribute seleccionado no existe.',

            'time_slot_id.required' => 'La :attribute es obligatoria.',
            'time_slot_id.integer' => 'La :attribute debe ser un número.',
            'time_slot_id.exists' => 'La :attribute seleccionada no existe.',

            'classroom_id.required' => 'El :attribute es obligatorio.',
            'classroom_id.integer' => 'El :attribute debe ser un número.',
            'classroom_id.exists' => 'El :attribute seleccionado no existe.',

            'day_id.required' => 'El :attribute es obligatorio.',
            'day_id.integer' => 'El :attribute debe ser un número.',
            'day_id.exists' => 'El :attribute seleccionado no existe.',

            'start_time.required' => 'La :attribute es obligatoria.',
            'start_time.date_format' => 'La :attribute debe tener formato HH:MM (24h).',

            'end_time.required' => 'La :attribute es obligatoria.',
            'end_time.date_format' => 'La :attribute debe tener formato HH:MM (24h).',
            'end_time.after' => 'La :attribute debe ser mayor que la hora de inicio.',
        ];
    }

    public function attributes()
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