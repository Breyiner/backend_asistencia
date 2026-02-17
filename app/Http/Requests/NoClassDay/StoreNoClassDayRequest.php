<?php

namespace App\Http\Requests\NoClassDay;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoClassDayRequest extends FormRequest
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
            'ficha_id' => ['required', 'integer', 'exists:fichas,id'],
            'reason_id' => ['required', 'integer', 'exists:no_class_reasons,id'],
            'date' => ['required', 'date'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ficha_id.required' => 'La :attribute es obligatoria.',
            'ficha_id.integer' => 'La :attribute debe ser un número.',
            'ficha_id.exists' => 'La :attribute seleccionada no existe.',

            'reason_id.required' => 'El :attribute es obligatorio.',
            'reason_id.integer' => 'El :attribute debe ser un número.',
            'reason_id.exists' => 'El :attribute seleccionado no existe.',

            'date.required' => 'La :attribute es obligatoria.',
            'date.date' => 'La :attribute no tiene un formato válido.',

            'observations.string' => 'Las :attribute deben ser un texto.',
            'observations.max' => 'Las :attribute no pueden superar los :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'reason_id' => 'motivo',
            'date' => 'fecha',
            'observations' => 'observaciones',
        ];
    }
}