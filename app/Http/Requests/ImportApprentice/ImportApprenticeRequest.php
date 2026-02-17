<?php

namespace App\Http\Requests\ImportApprentice;

use Illuminate\Foundation\Http\FormRequest;

class ImportApprenticeRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El :attribute es obligatorio',
            'file.file' => 'Debe ser archivo',
            'file.mimes' => 'El :attribute debe ser formato Excel',
            'file.max' => 'El :attribute debe pesar máximo 5MB'
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo'
        ];
    }    
}
