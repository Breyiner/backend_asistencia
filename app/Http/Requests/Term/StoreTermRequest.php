<?php

namespace App\Http\Requests\Term;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** de trimestre (Term).
 *
 * Reglas: nombre único con formato estricto "Trimestre [1-7]".
 */
class StoreTermRequest extends FormRequest
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
     * **Formato estricto:** `Trimestre 1`, `Trimestre 2`, `Trimestre 3`, `Trimestre 4` ...
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^Trimestre [1-7]$/i',
                'unique:terms,name'
            ],
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
            'name.regex' => 'El :attribute debe seguir el formato "Trimestre X" (X=1-4).',
            'name.unique' => 'El :attribute ya existe.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del trimestre',
        ];
    }
}
