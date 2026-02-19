<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZAR** asistencia (checkout/tardanza).
 *
 * Condicionales: required_unless/required_if status/checkout.
 */
class UpdateAttendanceRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas condicionales status/hora.
     */
    public function rules(): array
    {
        return [
            'attendance_status_id' => [
                'required_unless:checkout,true',
                'nullable',
                'integer',
                'exists:attendance_statuses,id',
            ],
            'entry_hour' => [
                'required_if:attendance_status_id,4',
                'nullable',
                'date_format:H:i',
            ],
            'observations' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'checkout.boolean' => 'El :attribute debe ser verdadero o falso.',
            'attendance_status_id.required_unless' => 'El :attribute es obligatorio.',
            'attendance_status_id.integer' => 'El :attribute debe ser un número.',
            'attendance_status_id.exists' => 'El :attribute no existe.',
            'entry_hour.required_if' => 'La :attribute es obligatoria para tardanza.',
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
            'checkout' => 'checkout',
            'attendance_status_id' => 'estado de asistencia',
            'entry_hour' => 'hora de entrada',
            'observations' => 'observaciones',
        ];
    }
}
