<?php

namespace App\Http\Requests\ScheduleSession;

use App\Models\ScheduleSession;
use App\Models\Shift;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = (int) $this->route('schedule_session_id');

        $session = ScheduleSession::find($id);

        $shiftId = $this->input('shift_id') ?? ($session?->shift_id);
        $classroomId = $this->input('classroom_id') ?? ($session?->classroom_id);

        return [
            'instructor_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
                 new UserHasRole('Instructor')
            ],
            'shift_id' => [
                'sometimes',
                'integer',
                'exists:shifts,id',
            ],
            'classroom_id' => [
                'sometimes',
                'integer',
                'exists:classrooms,id',

                // Un ambiente único por jornada (ignorando este registro)
                Rule::unique('schedule_sessions', 'classroom_id')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('shift_id', $shiftId)),
            ],
            'day_id' => [
                'sometimes',
                'integer',
                'exists:days,id',
            ],
            'start_time' => [
                'sometimes',
                'date_format:H:i',
            ],
            'end_time' => [
                'sometimes',
                'date_format:H:i',
            ],

            // Un instructor único por ambiente + jornada (ignorando este registro)
            'instructor_unique' => [
                Rule::unique('schedule_sessions', 'instructor_id')
                    ->ignore($id)
                    ->where(
                        fn($q) => $q
                            ->where('shift_id', $shiftId)
                            ->where('classroom_id', $classroomId)
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

            $id = (int) $this->route('schedule_session_id');
            $session = ScheduleSession::find($id);
            if (!$session) return;

            $shiftId = $this->input('shift_id') ?? $session->shift_id;
            $start = $this->input('start_time') ?? substr($session->start_time, 0, 5);

            $shift = Shift::find($shiftId);
            if (!$shift) return;

            $shiftStart = substr($shift->start_time, 0, 5);
            $shiftEnd = substr($shift->end_time, 0, 5);

            if ($shiftEnd >= $shiftStart) {
                if ($start < $shiftStart || $start > $shiftEnd) {
                    $validator->errors()->add(
                        'start_time',
                        'La :attribute debe estar dentro del rango de la jornada.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'classroom_id.unique' => 'El :attribute ya está ocupado en esta jornada.',
            'instructor_unique.unique' => 'El :attribute ya está asignado a ese ambiente en esa jornada.',

            'instructor_id.integer' => 'El :attribute debe ser un número.',
            'instructor_id.exists' => 'El :attribute seleccionado no existe.',

            'shift_id.integer' => 'El :attribute debe ser un número.',
            'shift_id.exists' => 'El :attribute seleccionado no existe.',

            'classroom_id.integer' => 'El :attribute debe ser un número.',
            'classroom_id.exists' => 'El :attribute seleccionado no existe.',

            'day_id.integer' => 'El :attribute debe ser un número.',
            'day_id.exists' => 'El :attribute seleccionado no existe.',

            'start_time.date_format' => 'La :attribute debe tener formato HH:MM (24h).',
            'end_time.date_format' => 'La :attribute debe tener formato HH:MM (24h).',
        ];
    }

    public function attributes(): array
    {
        return [
            'instructor_id' => 'instructor',
            'shift_id' => 'jornada',
            'classroom_id' => 'ambiente',
            'day_id' => 'día',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'instructor_unique' => 'instructor',
        ];
    }
}
