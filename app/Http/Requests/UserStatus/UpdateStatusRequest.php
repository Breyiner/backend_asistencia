<?php

namespace App\Http\Requests\UserStatus;

use Illuminate\Foundation\Http\FormRequest;


/**
 * Validación **ACTUALIZACIÓN PARCIAL** de estado usuario (PATCH).
 *
 * Campos opcionales para updates mínimos.
 */
class PartialUpdateStatusRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación (PATCH).
     * 
     * **`sometimes`:** valida solo si el campo está presente
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|min:5|max:20',
            'description' => 'sometimes|required|string|min:10|max:50',
        ];
    }

    /**
     * Mensajes de error personalizados.
     * 
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.min' => 'El :attribute debe tener al menos :min caracteres.',
            'name.max' => 'El :attribute no debe tener más de :max caracteres.',

            'description.required' => 'La :attribute es obligatoria.',
            'description.string' => 'La :attribute debe ser texto.',
            'description.min' => 'La :attribute debe tener al menos :min caracteres.',
            'description.max' => 'La :attribute no debe tener más de :max caracteres.',
        ];
    }

    /**
     * Atributos legibles.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
        ];
    }
}
