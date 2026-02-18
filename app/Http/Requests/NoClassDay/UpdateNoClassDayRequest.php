<?php

namespace App\Http\Requests\NoClassDay;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** día sin clase SENA.
 *
 * Reglas: sometimes + ficha + motivo + fecha.
 */
class UpdateNoClassDayRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (UPDATE).
     */
    public function rules(): array
    {
        return [
            'ficha_id' => ['sometimes', 'required', 'integer', 'exists:fichas,id'],
            'reason_id' => ['sometimes', 'required', 'integer', 'exists:no_class_reasons,id'],
            'date' => ['sometimes', 'required', 'date'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'ficha_id.required' => 'La :attribute es obligatoria.',
            'ficha_id.integer' => 'La :attribute debe ser un número.',
            'ficha_id.exists' => 'La :attribute seleccionada no existe.',
            'reason_id.required' => 'El :attribute es obligatorio.',
            'reason_id.integer' => 'El :attribute debe ser un número.',
            'reason_id.exists' => 'El :attribute seleccionado no existe.',
            'date.required' => 'La :attribute es obligatoria.',
            'date.date' => 'La :attribute no tiene un formato válido.',
            'observations.string' => 'Las :attribute deben ser un texto.',
            'observations.max' => 'Las :attribute no pueden superar los :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'reason_id' => 'motivo',
            'date' => 'fecha',
            'observations' => 'observaciones',
        ];
    }
}
