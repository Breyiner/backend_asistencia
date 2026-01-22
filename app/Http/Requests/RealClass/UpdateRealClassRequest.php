<?php

namespace App\Http\Requests\RealClass;

use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRealClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('real_class_id');
        
        return [
            'instructor_id' => ['sometimes', 'required', 'exists:users,id', new UserHasRole('Instructor')],
            'class_type_id' => ['sometimes', 'required', 'exists:class_types,id'],
            'classroom_id' => ['sometimes', 'required', 'exists:classrooms,id'],
            'shift_id' => ['sometimes', 'required', 'exists:shifts,id'],
            'schedule_session_id' => ['sometimes', 'required', 'exists:schedule_sessions,id'],
            'start_hour' => ['sometimes', 'required', 'date_format:H:i'],
            'end_hour' => ['sometimes', 'required', 'date_format:H:i', 'after:start_hour'],
            'original_date' => ['sometimes', 'nullable', 'date', 'required_if:class_type_id,3'],
            'observations' => ['sometimes', 'required', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'instructor_id.required' => 'El :attribute es obligatorio.',
            'instructor_id.exists' => 'El :attribute no existe.',

            'class_type_id.required' => 'El :attribute es obligatorio.',
            'class_type_id.exists' => 'El :attribute no existe.',

            'classroom_id.required' => 'El :attribute es obligatorio.',
            'classroom_id.exists' => 'El :attribute no existe.',

            'shift_id.required' => 'El :attribute es obligatorio.',
            'shift_id.exists' => 'El :attribute no existe.',

            'schedule_session_id.required' => 'La :attribute es obligatoria.',
            'schedule_session_id.exists' => 'La :attribute no existe.',
            
            'start_hour.required' => 'La :attribute es obligatoria.',
            'start_hour.date_format' => 'La :attribute debe ser HH:MM.',

            'end_hour.required' => 'La :attribute es obligatoria.',
            'end_hour.date_format' => 'La :attribute debe ser HH:MM.',
            'end_hour.after' => 'La :attribute debe ser después de hora inicio.',

            'original_date.required' => 'La :attribute es obligatoria para clases de recuperación.',
            'original_date.date' => 'La :attribute debe ser fecha válida.',

            'observations.string' => 'Las :attribute deben ser texto.',
            'observations.max' => 'Las :attribute no pueden exceder 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'instructor_id' => 'instructor',
            'class_type_id' => 'tipo de clase',
            'classroom_id' => 'aula',
            'shift_id' => 'jornada',
            'schedule_session_id' => 'la formación del horario',
            'start_hour' => 'hora inicio',
            'end_hour' => 'hora fin',
            'original_date' => 'fecha original',
            'observations' => 'observaciones',
        ];
    }
}
