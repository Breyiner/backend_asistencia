<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** aula/ambiente (parcial).
 *
 * Reglas: sometimes (solo campos enviados).
 */
class UpdateClassroomRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación parcial (UPDATE).
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:80'
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:255'
            ],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
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
            'name' => 'nombre del ambiente',
            'description' => 'descripción',
        ];
    }
}
