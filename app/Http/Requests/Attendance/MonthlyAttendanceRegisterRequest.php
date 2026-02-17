<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyAttendanceRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $now = now('America/Bogota');

        $this->mergeIfMissing([
            'year' => (int) $now->year,
            'month' => (int) $now->month,
        ]);
    }

    public function rules(): array
    {
        return [
            'ficha_id' => ['required', 'integer', 'exists:fichas,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'ficha_id.required' => 'La :attribute es obligatoria.',
            'ficha_id.integer' => 'La :attribute debe ser un número.',
            'ficha_id.exists' => 'La :attribute no existe.',

            'year.required' => 'El :attribute es obligatorio.',
            'year.integer' => 'El :attribute debe ser un número.',
            'year.min' => 'El :attribute no es válido.',
            'year.max' => 'El :attribute no es válido.',

            'month.required' => 'El :attribute es obligatorio.',
            'month.integer' => 'El :attribute debe ser un número.',
            'month.min' => 'El :attribute debe estar entre :min y :max.',
            'month.max' => 'El :attribute debe estar entre :min y :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'year' => 'año',
            'month' => 'mes',
        ];
    }
}