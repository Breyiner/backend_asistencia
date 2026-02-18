<?php

namespace App\Http\Requests\Ficha;

use App\Rules\UserHasRoleCode;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** ficha SENA.
 *
 * Reglas: sometimes + gestor con rol GESTOR_FICHAS + unique ignorando ficha actual + status_id requerido.
 */
class UpdateFichaRequest extends FormRequest
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
        $fichaId = $this->route('ficha_id');

        return [
            'gestor_id' => ['sometimes', 'required', 'integer', 'exists:users,id', new UserHasRoleCode('GESTOR_FICHAS')],
            'ficha_number' => ['sometimes', 'required', 'string', "unique:fichas,ficha_number,{$fichaId},id", 'digits_between:5,20'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'training_program_id' => ['sometimes', 'required', 'integer', 'exists:training_programs,id'],
            'status_id' => ['sometimes', 'required', 'integer', 'exists:ficha_statuses,id'],
            'shift_id' => ['sometimes', 'required', 'integer', 'exists:shifts,id'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'gestor_id.required' => 'El :attribute es obligatorio.',
            'gestor_id.integer' => 'El :attribute debe ser un número entero.',
            'gestor_id.exists' => 'El :attribute seleccionado no existe.',
            'ficha_number.required' => 'El :attribute es obligatorio.',
            'ficha_number.string' => 'El :attribute debe ser en formato de texto.',
            'ficha_number.unique' => 'El :attribute ya está en uso.',
            'ficha_number.digits_between' => 'El :attribute debe tener entre :min y :max caracteres.',
            'start_date.required' => 'La :attribute es obligatoria.',
            'start_date.date' => 'La :attribute debe ser una fecha válida.',
            'end_date.required' => 'La :attribute es obligatoria.',
            'end_date.date' => 'La :attribute debe ser una fecha válida.',
            'end_date.after' => 'La :attribute debe ser una fecha posterior a la fecha de inicio.',
            'training_program_id.required' => 'El :attribute es obligatorio.',
            'training_program_id.integer' => 'El :attribute debe ser un número entero.',
            'training_program_id.exists' => 'El :attribute seleccionado no existe.',
            'status_id.required' => 'El :attribute es obligatorio.',
            'status_id.integer' => 'El :attribute debe ser un número entero.',
            'status_id.exists' => 'El :attribute seleccionado no existe.',
            'shift_id.required' => 'La :attribute es obligatoria.',
            'shift_id.integer' => 'El :attribute debe ser un número entero.',
            'shift_id.exists' => 'El :attribute seleccionado no existe.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'gestor_id' => 'gestor',
            'ficha_number' => 'número de ficha',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de finalización',
            'training_program_id' => 'programa de formación',
            'status_id' => 'estado',
            'shift_id' => 'jornada',
        ];
    }
}
