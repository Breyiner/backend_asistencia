<?php

namespace App\Http\Requests\FichaTerm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación **CREACIÓN** término de ficha SENA.
 *
 * Reglas: ficha + term único por ficha + fechas coherentes + fase.
 */
class StoreFichaTermRequest extends FormRequest
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
            'ficha_id' => ['required', 'exists:fichas,id'],
            'term_id' => [
                'required',
                'exists:terms,id',
                Rule::unique('ficha_terms')->where(function ($query) {
                    return $query->where('ficha_id', $this->ficha_id)
                                 ->where('term_id', $this->term_id);
                })
            ],
            'phase_id' => [
                'required',
                'exists:phases,id'
            ],
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['boolean'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'ficha_id.required' => 'El :attribute es obligatorio.',
            'ficha_id.exists' => 'El :attribute seleccionado no existe.',
            'term_id.required' => 'El :attribute es obligatorio.',
            'term_id.exists' => 'El :attribute seleccionado no existe.',
            'term_id.unique' => 'Esta ficha ya tiene asignado este :attribute.',
            'phase_id.required' => 'El :attribute es obligatorio.',
            'phase_id.exists' => 'El :attribute seleccionado no existe.',
            'start_date.required' => 'El :attribute es obligatorio.',
            'start_date.date' => 'El :attribute debe ser una fecha válida.',
            'start_date.before' => 'El :attribute debe ser anterior a la fecha fin.',
            'end_date.required' => 'El :attribute es obligatorio.',
            'end_date.date' => 'El :attribute debe ser una fecha válida.',
            'end_date.after' => 'El :attribute debe ser posterior a la fecha inicio.',
            'is_current.boolean' => 'El :attribute debe ser verdadero o falso.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'term_id' => 'trimestre',
            'phase_id' => 'fase',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha fin',
            'is_current' => 'activo',
        ];
    }
}
