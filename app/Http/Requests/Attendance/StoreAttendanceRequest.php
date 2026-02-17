<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
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
            'real_class_id' => ['required', 'exists:real_classes,id'],
            'apprentice_id' => ['required', 'exists:users,id'],
            'attendance_status_id' => ['sometimes', 'required', 'exists:attendance_statuses,id'],
            'entry_hour' => ['nullable', 'date_format:H:i'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

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
