<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'document_number' => ['required', 'string', 'min:6', 'max:20', 'unique:users,document_number'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'max:50', new StrongPassword()]
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

            'password.required' => 'La :attribute es obligatoria',
            'password.string' => 'La :attribute debe ser texto',
            'password.max' => 'La :attribute no debe tener más de :max caracteres',

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
            'email' => 'correo',
            'password' => 'contraseña',
            'document_type_id' => 'tipo de documento',
            'document_number' => 'número de documento',
        ];
    }
}
