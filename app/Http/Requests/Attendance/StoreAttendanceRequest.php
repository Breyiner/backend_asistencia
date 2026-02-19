<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREAR** asistencia manual.
 *
 * Campos: real_class + apprentice + status opcional.
 */
class StoreAttendanceRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación asistencia completa.
     */
    public function rules(): array
    {
        return [
            'real_class_id' => ['required', 'exists:real_classes,id'],
            'apprentice_id' => ['required', 'exists:users,id'],
            'attendance_status_id' => ['sometimes', 'required', 'exists:attendance_statuses,id'],
            'entry_hour' => ['nullable', 'date_format:H:i'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'real_class_id.required' => 'La :attribute es obligatoria.',
            'real_class_id.exists' => 'La :attribute no existe.',
            'apprentice_id.required' => 'El :attribute es obligatorio.',
            'apprentice_id.exists' => 'El :attribute no existe.',
            'attendance_status_id.required' => 'El :attribute es obligatorio.',
            'attendance_status_id.exists' => 'El :attribute no existe.',
            'entry_hour.date_format' => 'La :attribute debe ser en formato HH:MM.',
            'observations.string' => 'Las :attribute deben ser texto.',
            'observations.max' => 'Las :attribute no pueden exceder :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'real_class_id' => 'clase real',
            'apprentice_id' => 'aprendiz',
            'attendance_status_id' => 'estado de asistencia',
            'entry_hour' => 'hora de entrada',
            'observations' => 'observaciones',
        ];
    }
}
