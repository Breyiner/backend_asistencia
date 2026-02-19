<?php

namespace App\Http\Requests\ClassType;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** tipo de clase.
 *
 * Reglas: unique ignore ID.
 */
class UpdateClassTypeRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación + ignore ID (UPDATE).
     */
    public function rules(): array
    {
        $id = $this->route('class_type_id');
        
        return [
            'name' => ['required', 'string', 'max:50', "unique:class_types,name,{$id}"],
            'description' => ['nullable', 'string', 'max:255'],
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
            'name.unique' => 'El :attribute ya existe.',
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
