<?php

namespace App\Http\Requests\AttendanceStatus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** estado asistencia (PRESENTE, AUSENTE, TARDÍA).
 *
 * Reglas: code/name únicos en attendance_statuses.
 */
class StoreAttendanceStatusRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (CREATE).
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:attendance_statuses,code'],
            'name' => ['required', 'string', 'max:50', 'unique:attendance_statuses,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El :attribute es obligatorio.',
            'code.string' => 'El :attribute debe ser texto.',
            'code.max' => 'El :attribute no puede exceder :max caracteres.',
            'code.unique' => 'El :attribute ya existe.',
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.unique' => 'El :attribute ya existe.',
            'description.string' => 'La :attribute debe ser texto.',
            'description.max' => 'La :attribute no puede exceder :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'code' => 'código del estado de asistencia',
            'name' => 'nombre del estado de asistencia',
            'description' => 'descripción del estado',
        ];
    }
}
