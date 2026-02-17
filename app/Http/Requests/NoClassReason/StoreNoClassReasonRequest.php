<?php

namespace App\Http\Requests\NoClassReason;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoClassReasonRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:no_class_reasons,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser un texto.',
            'name.max' => 'El :attribute no puede superar los :max caracteres.',
            'name.unique' => 'Este motivo ya existe.',

            'description.string' => 'La :attribute debe ser un texto.',
            'description.max' => 'La :attribute no puede superar los :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del motivo',
            'description' => 'descripción',
        ];
    }
}