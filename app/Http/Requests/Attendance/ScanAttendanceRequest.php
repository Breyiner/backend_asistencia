<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ESCÁNER** asistencia (codigo de barras).
 *
 * Verifica: document_number existe en users.
 */
class ScanAttendanceRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación documento único.
     */
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

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'El :attribute es obligatorio.',
            'document_number.string' => 'El :attribute debe ser texto.',
            'document_number.max' => 'El :attribute no puede exceder :max caracteres.',
            'document_number.exists' => 'No existe un aprendiz con ese :attribute.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'document_number' => 'número de documento',
        ];
    }
}
