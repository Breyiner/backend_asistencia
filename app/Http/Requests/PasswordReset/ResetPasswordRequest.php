<?php

namespace App\Http\Requests\PasswordReset;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as LaravelStrongPassword;

/**
 * Validación **RESETEO** contraseña con token.
 *
 * Reglas: token + email + password fuerte confirmado.
 */
class ResetPasswordRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (RESET).
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'string', 'max:50', 'confirmed', LaravelStrongPassword::defaults()],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'token.required' => 'El :attribute es requerido.',
            'token.string' => 'El :attribute debe ser texto.',
            'email.required' => 'El :attribute es obligatorio.',
            'email.email' => 'El :attribute debe ser un email válido.',
            'password.required' => 'La :attribute es obligatoria.',
            'password.string' => 'La :attribute debe ser texto.',
            'password.max' => 'La :attribute no puede exceder :max caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'token' => 'token',
            'email' => 'correo electrónico',
            'password' => 'nueva contraseña',
        ];
    }
}
