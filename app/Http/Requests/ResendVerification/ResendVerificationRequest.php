<?php

namespace App\Http\Requests\ResendVerification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **REENVÍO** email verificación.
 *
 * Reglas: email requerido y válido.
 */
class ResendVerificationRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (RESEND).
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El :attribute es obligatorio.',
            'email.email' => 'El :attribute debe tener formato válido.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'email' => 'correo',
        ];
    }
}
