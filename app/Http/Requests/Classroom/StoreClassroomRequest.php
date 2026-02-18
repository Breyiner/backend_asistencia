<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** aula/ambiente (LAB-101, SALA-1).
 *
 * Reglas: name required, description opcional.
 */
class StoreClassroomRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:80'
            ],
            'description' => [
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
            'name.required' => 'El :attribute es obligatorio.',
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
