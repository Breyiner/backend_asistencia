<?php

namespace App\Http\Requests\User;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CAMBIO DE CONTRASEÑA** usuario propio.
 *
 * Reglas: contraseña actual + nueva fuerte + confirmación.
 */
class UpdateOwnUserPasswordRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios (cambio propio).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación contraseña.
     * 
     * **StrongPassword:** regla personalizada (mínimo 8, mayúscula, etc.)
     * **confirmed:** requiere `new_password_confirmation`
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'max:50', new StrongPassword(), 'confirmed']
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Debes ingresar tu :attribute.',
            'current_password.string' => 'La :attribute debe ser texto.',

            'new_password.required' => 'La :attribute es obligatoria.',
            'new_password.string' => 'La :attribute debe ser texto.',
            'new_password.max' => 'La :attribute no debe tener más de :max caracteres.',
            'new_password.confirmed' => 'La confirmación de la :attribute no coincide.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'new_password' => 'nueva contraseña',
            'current_password' => 'contraseña actual'
        ];
    }
}
