<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** de jornada (Shift).
 *
 * Reglas: nombre único, texto máximo 50 caracteres.
 */
class StoreShiftRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación básica (CREATE).
     * 
     * Nombre obligatorio y único en tabla `shifts`.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:shifts,name'
            ]
        ];
    }

    /**
     * Mensajes de error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.unique' => 'El :attribute ya está registrado.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre de la jornada',
        ];
    }
}
