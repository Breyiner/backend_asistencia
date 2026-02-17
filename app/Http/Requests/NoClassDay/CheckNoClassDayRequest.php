<?php

namespace App\Http\Requests\NoClassDay;

use Illuminate\Foundation\Http\FormRequest;

class CheckNoClassDayRequest extends FormRequest
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
            'date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'ficha_id.required' => 'La ficha es obligatoria.',
            'ficha_id.integer' => 'La ficha debe ser un número.',
            'ficha_id.exists' => 'La ficha seleccionada no existe.',

            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'date' => 'fecha',
        ];
    }
}
