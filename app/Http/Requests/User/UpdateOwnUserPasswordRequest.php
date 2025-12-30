<?php

namespace App\Http\Requests\User;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnUserPasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'max:50', new StrongPassword(), 'confirmed']
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Debes ingresar tu :attribute.',
            'current_password.string' => 'La :attribute debe ser texto',

            'password.required' => 'La :attribute es obligatoria.',
            'password.string' => 'La :attribute debe ser texto',
            'password.max' => 'La :attribute no debe tener más de :max caracteres.',
            'password.confirmed' => 'La confirmación de la :attribute no coincide.',
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
            'password' => 'nueva contraseña',
            'current_password' => 'contrasenña actual'
        ];
    }
}