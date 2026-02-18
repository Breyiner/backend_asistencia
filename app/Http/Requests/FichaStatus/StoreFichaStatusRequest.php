<?php

namespace App\Http\Requests\FichaStatus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** estado de ficha SENA.
 *
 * Reglas: name + description con límites de caracteres.
 */
class StoreFichaStatusRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:5', 'max:20'],
            'description' => ['required', 'string', 'min:10', 'max:50'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser en formato de texto.',
            'name.min' => 'El :attribute debe tener al menos :min caracteres.',
            'name.max' => 'El :attribute no debe tener más de :max caracteres.',
            'description.required' => 'La :attribute es obligatoria.',
            'description.string' => 'La :attribute debe ser en formato de texto.',
            'description.min' => 'La :attribute debe tener al menos :min caracteres.',
            'description.max' => 'La :attribute no debe tener más de :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
        ];
    }
}
