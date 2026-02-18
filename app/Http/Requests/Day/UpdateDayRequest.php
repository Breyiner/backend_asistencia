<?php

namespace App\Http\Requests\Day;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** día semana (parcial).
 *
 * Reglas: sometimes (NO unique, permite reutilizar).
 */
class UpdateDayRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:20'],
            'day_number' => ['sometimes', 'integer', 'between:1,7'],
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
            'day_number.integer' => 'El :attribute debe ser número.',
            'day_number.between' => 'El :attribute debe estar entre 1 y 7.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del día',
            'day_number' => 'número del día',
        ];
    }
}
