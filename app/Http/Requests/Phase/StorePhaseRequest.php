<?php

namespace App\Http\Requests\Phase;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** fase SENA.
 *
 * Reglas: nombre único AlphaSpaces + descripción opcional.
 */
class StorePhaseRequest extends FormRequest
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
            'name' => ['required', new AlphaSpaces(), 'string', 'min:3', 'max:50', 'unique:phases,name'],
            'description' => ['nullable', 'string', 'min:10', 'max:255', new AlphaSpaces()],
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
            'name.unique' => 'El :attribute ya existe.',
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
            'name' => 'nombre de la fase',
            'description' => 'descripción',
        ];
    }
}
