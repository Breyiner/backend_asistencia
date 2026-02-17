<?php

namespace App\Http\Requests\Day;

use Illuminate\Foundation\Http\FormRequest;

class StoreDayRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:20'],
            'day_number' => ['required', 'integer', 'between:1,7', 'unique:days'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            
            'day_number.required' => 'El :attribute es obligatorio.',
            'day_number.integer' => 'El :attribute debe ser número.',
            'day_number.between' => 'El :attribute debe estar entre 1 y 7.',
            'day_number.unique' => 'El :attribute ya está registrado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del día',
            'day_number' => 'número del día',
        ];
    }
}
