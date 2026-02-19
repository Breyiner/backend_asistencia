<?php

namespace App\Http\Requests\NotificationType;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** tipo de notificación.
 *
 * Reglas: sometimes + key único ignorando registro actual.
 */
class UpdateNotificationTypeRequest extends FormRequest
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
        $notificationTypeId = (int) $this->route('notification_type_id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                "unique:notification_types,key,{$notificationTypeId},id",
            ],
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
