<?php

namespace App\Http\Requests\Day;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDayRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:20'],
            'day_number' => ['sometimes', 'integer', 'between:1,7'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            
            'day_number.integer' => 'El :attribute debe ser número.',
            'day_number.between' => 'El :attribute debe estar entre 1 y 7.',
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
