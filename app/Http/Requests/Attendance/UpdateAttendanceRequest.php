<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
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
        return [
            'checkout' => ['sometimes', 'boolean'],

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
            
            'exit_hour' => [
                // 'required_if:attendance_status_id,5',
                'prohibited_unless:attendance_status_id,5',
                'nullable',
                'date_format:H:i',
            ],

            'observations' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'checkout.boolean' => 'El :attribute debe ser verdadero o falso.',

            'attendance_status_id.required_unless' => 'El :attribute es obligatorio.',
            'attendance_status_id.integer' => 'El :attribute debe ser un número.',
            'attendance_status_id.exists' => 'El :attribute no existe.',

            'entry_hour.required_if' => 'La :attribute es obligatoria para tardanza.',
            'entry_hour.date_format' => 'La :attribute debe ser en formato HH:MM.',

            'exit_hour.required_if' => 'La :attribute es obligatoria para salida anticipada.',
            'exit_hour.prohibited_unless' => 'La :attribute solo se permite cuando el estado es salida anticipada.',
            'exit_hour.date_format' => 'La :attribute debe ser en formato HH:MM.',

            'observations.string' => 'Las :attribute deben ser texto.',
            'observations.max' => 'Las :attribute no pueden exceder :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'checkout' => 'checkout',
            'attendance_status_id' => 'estado de asistencia',
            'entry_hour' => 'hora de entrada',
            'exit_hour' => 'hora de salida',
            'observations' => 'observaciones',
        ];
    }
}
