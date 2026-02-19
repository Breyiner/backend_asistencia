<?php

namespace App\Http\Requests\ImportApprentice;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **IMPORTACIÓN** aprendices desde Excel.
 *
 * Reglas: archivo Excel máximo 5MB.
 */
class ImportApprenticeRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (IMPORT).
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'file.required' => 'El :attribute es obligatorio.',
            'file.file' => 'Debe ser archivo.',
            'file.mimes' => 'El :attribute debe ser formato Excel.',
            'file.max' => 'El :attribute debe pesar máximo 5MB.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'file' => 'archivo',
        ];
    }
}
