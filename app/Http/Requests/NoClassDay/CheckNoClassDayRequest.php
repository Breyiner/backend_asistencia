<?php

namespace App\Http\Requests\NoClassDay;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CONSULTA** día sin clase por ficha/fecha.
 *
 * Reglas: ficha existente + fecha válida.
 */
class CheckNoClassDayRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (CHECK).
     */
    public function rules(): array
    {
        return [
            'ficha_id' => ['required', 'integer', 'exists:fichas,id'],
            'date' => ['required', 'date'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'ficha_id.required' => 'La :attribute es obligatoria.',
            'ficha_id.integer' => 'La :attribute debe ser un número.',
            'ficha_id.exists' => 'La :attribute seleccionada no existe.',
            'date.required' => 'La :attribute es obligatoria.',
            'date.date' => 'La :attribute no tiene un formato válido.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'date' => 'fecha',
        ];
    }
}
