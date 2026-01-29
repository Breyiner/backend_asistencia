<?php

namespace App\Http\Requests\Ficha;

use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreFichaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gestor_id' => ['required', 'integer', 'exists:users,id', new UserHasRole('Gestor de Fichas')],
            'ficha_number' => ['required', 'string', 'unique:fichas,ficha_number', 'digits_between:5,20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'training_program_id' => ['required', 'integer', 'exists:training_programs,id'],

            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
        ];
    }

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

            'shift_id.integer' => 'El :attribute debe ser un número entero.',
            'shift_id.exists' => 'El :attribute seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'gestor_id' => 'gestor',
            'ficha_number' => 'número de ficha',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de finalización',
            'training_program_id' => 'programa de formación',
            'shift_id' => 'jornada',
        ];
    }
}