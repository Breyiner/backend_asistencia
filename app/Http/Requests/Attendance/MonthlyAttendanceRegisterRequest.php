<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **REPORTE MENSUAL** asistencias por ficha.
 *
 * Auto-merge: year/month actuales (Bogotá TZ).
 */
class MonthlyAttendanceRegisterRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Pre-llenado year/month actuales.
     */
    protected function prepareForValidation(): void
    {
        $now = now('America/Bogota');
        $this->mergeIfMissing([
            'year' => (int) $now->year,
            'month' => (int) $now->month,
        ]);
    }

    /**
     * Reglas validación ficha + período.
     */
    public function rules(): array
    {
        return [
            'ficha_id' => ['required', 'integer', 'exists:fichas,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
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

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'ficha_id' => 'ficha',
            'year' => 'año',
            'month' => 'mes',
        ];
    }
}
