<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ScanAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_number' => [
                'required',
                'string',
                'max:30',
                'exists:users,document_number',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.required' => 'El :attribute es obligatorio.',
            'document_number.string' => 'El :attribute debe ser texto.',
            'document_number.max' => 'El :attribute no puede exceder :max caracteres.',
            'document_number.exists' => 'No existe un aprendiz con ese :attribute.',
        ];
    }

    public function attributes(): array
    {
        return [
            'document_number' => 'número de documento',
        ];
    }
}