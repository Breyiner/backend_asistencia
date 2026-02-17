<?php

namespace App\Http\Requests\Area;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:5', 'max:100', 'unique:areas,name', new AlphaSpaces()],
            'description' => ['nullable', 'string', 'min:10', 'max:200', new AlphaSpaces()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser una cadena de texto.',
            'name.min' => 'El :attribute debe tener mínimo :min caracteres.',
            'name.max' => 'El :attribute debe tener máximo :max caracteres.',
            'name.unique' => 'El :attribute ya está en uso.',

            'description.string' => 'La :attribute debe ser una cadena de texto.',
            'description.min' => 'La :attribute debe tener mínimo :min caracteres.',
            'description.max' => 'La :attribute debe tener máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del área',
            'description' => 'descripción del área',
        ];
    }
}
