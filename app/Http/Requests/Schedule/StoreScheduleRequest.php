<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** horario por trimestre de ficha SENA.
 *
 * Reglas: ficha_term único + descripción opcional.
 */
class StoreScheduleRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:255'],
            'ficha_term_id' => ['required', 'exists:ficha_terms,id', 'unique:schedules,ficha_term_id'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'description.string' => 'La :attribute debe ser texto.',
            'description.max' => 'La :attribute no puede exceder :max caracteres.',
            'ficha_term_id.required' => 'El :attribute es obligatorio.',
            'ficha_term_id.exists' => 'El :attribute seleccionado no existe.',
            'ficha_term_id.unique' => 'Ya existe un horario para este :attribute.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'description' => 'descripción',
            'ficha_term_id' => 'trimestre de ficha',
        ];
    }
}
