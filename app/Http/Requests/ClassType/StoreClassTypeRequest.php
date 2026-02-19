<?php

namespace App\Http\Requests\ClassType;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** tipo de clase (TEÓRICA, PRÁCTICA, PROYECTO).
 *
 * Reglas: name único en class_types.
 */
class StoreClassTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50', 'unique:class_types,name'],
            'description' => ['nullable', 'string', 'max:100'],
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
            'name.unique' => 'Este :attribute ya existe.',
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
            'name' => 'nombre del tipo de clase',
            'description' => 'descripción del tipo de clase',
        ];
    }
}
