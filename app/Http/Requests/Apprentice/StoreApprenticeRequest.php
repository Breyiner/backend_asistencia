<?php

namespace App\Http\Requests\Apprentice;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

class StoreApprenticeRequest extends FormRequest
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
            'first_name' => ['required', 'string', new AlphaSpaces()],
            'last_name' => ['required', 'string', new AlphaSpaces()],
            'telephone_number' => ['required', 'string', 'size:10', 'regex:/^\d+$/'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'document_number' => ['required', 'string', 'min:6', 'max:20', 'unique:users,document_number'],
            'email' => ['required', 'email', 'unique:users'],
            'birt_date' => ['required', 'date', 'before:today'],
            'ficha_id' => ['required', 'integer', 'exists:fichas,ficha_id'],
        ];
    }

    /**

     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'first_name.required' => 'El :attribute es obligatorio.',
            'first_name.string' => 'El :attribute debe ser texto.',

            'last_name.required' => 'El :attribute es obligatorio.',
            'last_name.string' => 'El :attribute debe ser texto.',

            'telephone_number.required' => 'El :attribute es obligatorio.',
            'telephone_number.string' => 'El :attribute debe ser texto.',
            'telephone_number.size' => 'El :attribute debe tener exactamente :size dígitos.',
            'telephone_number.regex' => 'El :attribute solo puede contener números.',

            'document_type_id.required' => 'El :attribute es obligatorio',
            'document_type_id.integer' => 'El :attribute debe ser un número entero',
            'document_type_id.exists' => 'El :attribute no existe',

            'document_number.required' => 'El :attribute es obligatorio',
            'document_number.string' => 'El :attribute debe ser texto',
            'document_number.min' => 'El :attribute debe tener al menos :min caracteres',
            'document_number.max' => 'El :attribute no debe tener más de :max caracteres',
            'document_number.unique' => 'Este :attribute ya está registrado en el sistema',

            'email.required' => 'El :attribute es obligatorio',
            'email.email' => 'El :attribute debe tener formato válido',
            'email.unique' => 'Este :attribute ya está registrado en el sistema',

            'birth_date.required' => 'La :attribute es obligatoria',
            'birth_date.date' => 'Formato de fecha inválido',
            'birth_date.before' => 'La :attribute no puede ser futura',

            'ficha_id.required' => 'El :attribute es obligatorio',
            'ficha_id.integer' => 'El :attribute debe ser un número entero',
            'ficha_id.exists' => 'Ficha :input no existe',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'telephone_number' => 'teléfono',
            'email' => 'correo',
            'document_type_id' => 'tipo de documento',
            'document_number' => 'número de documento',
            'birth_date' => 'fecha de nacimiento',
            'ficha_id' => 'ficha'
        ];
    }
}
