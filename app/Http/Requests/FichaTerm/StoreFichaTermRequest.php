<?php

namespace App\Http\Requests\FichaTerm;

use Illuminate\Foundation\Http\FormRequest;

class StoreFichaTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ficha_id' => ['required', 'exists:fichas,id'],
            'term_id' => [
                'required',
                'exists:terms,id',
                "unique:ficha_terms,ficha_id,{$this->ficha_id},term_id"
            ],
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ficha_id.required' => 'El :attribute es obligatorio.',
            'ficha_id.exists' => 'El :attribute seleccionado no existe.',

            'term_id.required' => 'El :attribute es obligatorio.',
            'term_id.exists' => 'El :attribute seleccionado no existe.',
            'term_id.unique' => 'Esta ficha ya tiene asignado este :attribute.',
            
            'start_date.required' => 'El :attribute es obligatorio.',
            'start_date.date' => 'El :attribute debe ser una fecha válida.',
            'start_date.before' => 'El :attribute debe ser anterior a la fecha fin.',

            'end_date.required' => 'El :attribute es obligatorio.',
            'end_date.date' => 'El :attribute debe ser una fecha válida.',
            'end_date.after' => 'El :attribute debe ser posterior a la fecha inicio.',

            'is_active.boolean' => 'El :attribute debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'term_id' => 'trimestre',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha fin',
            'is_active' => 'activo',
        ];
    }
}
