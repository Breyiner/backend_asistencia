<?php

namespace App\Http\Requests\NotificationType;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** tipo de notificación.
 *
 * Reglas: name + key único.
 */
class StoreNotificationTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'unique:notification_types,key'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'key.required' => 'El :attribute es obligatorio.',
            'key.string' => 'El :attribute debe ser texto.',
            'key.max' => 'El :attribute no puede exceder :max caracteres.',
            'key.unique' => 'El :attribute ya existe.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del tipo de notificación',
            'key' => 'clave del tipo de notificación',
        ];
    }
}
