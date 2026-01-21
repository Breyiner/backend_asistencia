<?php

namespace App\Http\Requests\ScheduleSession;

use App\Models\Shift;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'instructor_unique' => $this->input('instructor_id'),
        ]);
    }

    public function rules(): array
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

            'shift_id' => [
                'required',
                'integer',
                'exists:shifts,id',
            ],

            'classroom_id' => [
                'required',
                'integer',
                'exists:classrooms,id',

                Rule::unique('schedule_sessions', 'classroom_id')
                    ->where(fn ($q) => $q
                        ->where('schedule_id', $this->schedule_id)
                        ->where('day_id', $this->day_id)
                        ->where('shift_id', $this->shift_id)
                    ),
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

            'instructor_unique' => [
                Rule::unique('schedule_sessions', 'instructor_id')
                    ->where(fn ($q) => $q
                        ->where('schedule_id', $this->schedule_id)
                        ->where('day_id', $this->day_id)
                        ->where('shift_id', $this->shift_id)
                        ->where('classroom_id', $this->classroom_id)
                    ),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Shift::find($this->shift_id);
            if (!$shift) {
                return;
            }

            $shiftStart = substr($shift->start_time, 0, 5);
            $shiftEnd   = substr($shift->end_time, 0, 5);

            $start = $this->start_time;
            $end   = $this->end_time;

            if ($shiftEnd >= $shiftStart) {
                if ($start < $shiftStart || $start > $shiftEnd) {
                    $validator->errors()->add(
                        'start_time',
                        'La hora de inicio debe estar dentro del rango de la jornada.'
                    );
                }

                if ($end < $shiftStart || $end > $shiftEnd) {
                    $validator->errors()->add(
                        'end_time',
                        'La hora de finalización debe estar dentro del rango de la jornada.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'classroom_id.unique' => 'El :attribute ya está ocupado en esta jornada para ese día.',
            'instructor_unique.unique' => 'El :attribute ya está asignado a ese ambiente en esa jornada para ese día.',

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
            'instructor_unique' => 'instructor',
        ];
    }
}