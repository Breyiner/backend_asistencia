<?php

namespace App\Http\Requests\NoClassReason;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** motivo día sin clase SENA.
 *
 * Reglas: nombre único + descripción opcional.
 */
class StoreNoClassReasonRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (CREATE).
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:no_class_reasons,name'],
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
