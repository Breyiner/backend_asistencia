<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:255'],
            'ficha_term_id' => ['required', 'exists:ficha_terms,id', 'unique:schedules,ficha_term_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => 'El :attribute debe ser texto.',
            'description.max' => 'El :attribute no puede exceder :max caracteres.',
            
            'ficha_term_id.required' => 'El :attribute es obligatorio.',
            'ficha_term_id.exists' => 'El :attribute seleccionado no existe.',
            'ficha_term_id.unique' => 'Ya existe un horario para este :attribute.',
        ];
    }

    public function attributes(): array
    {
        return [
            'description' => 'descripción',
            'ficha_term_id' => 'trimestre de ficha',
        ];
    }
}
