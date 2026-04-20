<?php

namespace App\Http\Requests\PasswordReset;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **SOLICITUD** restablecimiento contraseña por documento.
 *
 * Reglas: número documento requerido.
 */
class ForgotPasswordRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (FORGOT).
     */
    public function rules(): array
    {
        return [
            'document_number' => 'required|max:20',
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'El :attribute es requerido.',
            'document_number.max' => 'El :attribute no puede exceder :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'document_number' => 'número de documento',
        ];
    }
}
