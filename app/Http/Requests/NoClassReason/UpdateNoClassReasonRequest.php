<?php

namespace App\Http\Requests\NoClassReason;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación **ACTUALIZACIÓN** motivo día sin clase SENA.
 *
 * Reglas: sometimes + nombre único ignorando registro actual.
 */
class UpdateNoClassReasonRequest extends FormRequest
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
        $reasonId = $this->route('no_class_reason_id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('no_class_reasons', 'name')->ignore($reasonId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser un texto.',
            'name.max' => 'El :attribute no puede superar los :max caracteres.',
            'name.unique' => 'Este motivo ya existe.',
            'description.string' => 'La :attribute debe ser un texto.',
            'description.max' => 'La :attribute no puede superar los :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del motivo',
            'description' => 'descripción',
        ];
    }
}
