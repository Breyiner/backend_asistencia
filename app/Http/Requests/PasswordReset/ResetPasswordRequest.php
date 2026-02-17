<?php
namespace App\Http\Requests\PasswordReset;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as LaravelStrongPassword;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'string', 'max:50', 'confirmed', LaravelStrongPassword::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'El token es requerido.',
            'password.required' => 'La :attribute es obligatoria.',
            'email.required' => 'El :attribute es obligatorio.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function attributes(): array
    {
        return ['password' => 'nueva contraseña'];
    }
}
