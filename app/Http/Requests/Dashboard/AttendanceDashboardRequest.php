<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Presets: 7d | month | 30d | custom
            'preset' => ['nullable', 'string', Rule::in(['7d', 'month', '30d', 'custom'])],

            // Solo aplica si preset=custom (HTML date => YYYY-MM-DD)
            'from' => ['nullable', 'date_format:Y-m-d', 'required_if:preset,custom'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'required_if:preset,custom', 'after_or_equal:from'],

            // Filtros
            'training_program_id' => ['nullable', 'integer', 'exists:training_programs,id'],
            'ficha_id'            => ['nullable', 'integer', 'exists:fichas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'preset.string' => 'El :attribute debe ser texto.',
            'preset.in' => 'El :attribute debe ser uno de: 7d, month, 30d, custom.',

            'from.required_if' => 'La :attribute es obligatoria cuando el rango es personalizado.',
            'from.date_format' => 'La :attribute debe tener formato YYYY-MM-DD.',

            'to.required_if' => 'La :attribute es obligatoria cuando el rango es personalizado.',
            'to.date_format' => 'La :attribute debe tener formato YYYY-MM-DD.',
            'to.after_or_equal' => 'La :attribute debe ser mayor o igual que la fecha de inicio.',

            'training_program_id.integer' => 'El :attribute debe ser un número entero.',
            'training_program_id.exists' => 'El :attribute seleccionado no existe.',

            'ficha_id.integer' => 'La :attribute debe ser un número entero.',
            'ficha_id.exists' => 'La :attribute seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'preset' => 'preset',
            'from' => 'fecha de inicio',
            'to' => 'fecha de finalización',
            'training_program_id' => 'programa de formación',
            'ficha_id' => 'ficha',
        ];
    }
}