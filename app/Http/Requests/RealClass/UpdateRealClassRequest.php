<?php

namespace App\Http\Requests\RealClass;

use App\Rules\UserHasRoleCode;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** clase real SENA.
 *
 * Reglas: sometimes + instructor INSTRUCTOR + validaciones completas.
 */
class UpdateRealClassRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (UPDATE).
     */
    public function rules(): array
    {
        $id = $this->route('real_class_id');

        return [
            'instructor_id' => ['sometimes', 'required', 'exists:users,id', new UserHasRoleCode('INSTRUCTOR')],
            'class_type_id' => ['sometimes', 'required', 'exists:class_types,id'],
            'classroom_id' => ['sometimes', 'required', 'exists:classrooms,id'],
            'time_slot_id' => ['sometimes', 'required', 'exists:time_slots,id'],
            'schedule_session_id' => ['sometimes', 'required', 'exists:schedule_sessions,id'],
            'start_hour' => ['sometimes', 'required', 'date_format:H:i'],
            'end_hour' => ['sometimes', 'required', 'date_format:H:i', 'after:start_hour'],
            'original_date' => ['sometimes', 'nullable', 'date', 'required_if:class_type_id,3'],
            'observations' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'instructor_id.required' => 'El :attribute es obligatorio.',
            'instructor_id.exists' => 'El :attribute no existe.',
            'class_type_id.required' => 'El :attribute es obligatorio.',
            'class_type_id.exists' => 'El :attribute no existe.',
            'classroom_id.required' => 'El :attribute es obligatorio.',
            'classroom_id.exists' => 'El :attribute no existe.',
            'time_slot_id.required' => 'La :attribute es obligatoria.',
            'time_slot_id.exists' => 'La :attribute no existe.',
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

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'instructor_id' => 'instructor',
            'class_type_id' => 'tipo de clase',
            'classroom_id' => 'aula',
            'time_slot_id' => 'franja horaria',
            'schedule_session_id' => 'sesión del horario',
            'start_hour' => 'hora inicio',
            'end_hour' => 'hora fin',
            'original_date' => 'fecha original',
            'observations' => 'observaciones',
        ];
    }
}
