<?php

namespace App\Http\Requests\ScheduleSession;

use App\Models\ScheduleSession;
use App\Models\Shift;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function currentScheduleSessionId()
    {
        $param = $this->route('schedule_session_id');

        if (!$param) {
            return null;
        }

        return $param;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:users,id',
                new UserHasRole('Instructor'),
            ],

            'schedule_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:schedules,id',
            ],

            'shift_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:shifts,id',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $currentId = $this->currentScheduleSessionId();

            $start = $this->start_time;
            $end   = $this->end_time;

            $shift = Shift::find($this->shift_id);
            if ($shift) {
                $shiftStart = substr($shift->start_time, 0, 5);
                $shiftEnd   = substr($shift->end_time, 0, 5);

                if ($shiftEnd >= $shiftStart) {
                    if ($start < $shiftStart || $start > $shiftEnd) {
                        $validator->errors()->add('start_time', 'La hora de inicio debe estar dentro del rango de la jornada.');
                        return;
                    }

                    if ($end < $shiftStart || $end > $shiftEnd) {
                        $validator->errors()->add('end_time', 'La hora de finalización debe estar dentro del rango de la jornada.');
                        return;
                    }
                }
            }

            $classroomOverlap = ScheduleSession::query()
                ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                ->where('schedule_id', $this->schedule_id)
                ->where('day_id', $this->day_id)
                ->where('shift_id', $this->shift_id)
                ->where('classroom_id', $this->classroom_id)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($classroomOverlap) {
                $validator->errors()->add(
                    'classroom_id',
                    'El ambiente ya tiene una clase asignada en ese día y jornada que se cruza con el horario ingresado.'
                );
            }

            $instructorOverlap = ScheduleSession::query()
                ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                ->where('schedule_id', $this->schedule_id)
                ->where('day_id', $this->day_id)
                ->where('shift_id', $this->shift_id)
                ->where('instructor_id', $this->instructor_id)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($instructorOverlap) {
                $validator->errors()->add(
                    'instructor_id',
                    'El instructor ya tiene una clase asignada en ese día y jornada que se cruza con el horario ingresado.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'instructor_id.required' => 'El :attribute es obligatorio.',
            'instructor_id.integer' => 'El :attribute debe ser un número.',
            'instructor_id.exists' => 'El :attribute seleccionado no existe.',

            'schedule_id.required' => 'El :attribute es obligatorio.',
            'schedule_id.integer' => 'El :attribute debe ser un número.',
            'schedule_id.exists' => 'El :attribute seleccionado no existe.',

            'shift_id.required' => 'El :attribute es obligatorio.',
            'shift_id.integer' => 'El :attribute debe ser un número.',
            'shift_id.exists' => 'El :attribute seleccionado no existe.',

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

    public function attributes(): array
    {
        return [
            'instructor_id' => 'instructor',
            'schedule_id' => 'horario',
            'shift_id' => 'jornada',
            'classroom_id' => 'ambiente',
            'day_id' => 'día',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
        ];
    }
}