<?php

namespace App\Http\Requests\TrainingProgram;

use App\Rules\UserHasRoleCode;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** de programa de formación.
 *
 * Campos opcionales (`sometimes`) para partial updates.
 */
class UpdateTrainingProgramRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación (UPDATE).
     * 
     * **Diferencia clave:** `sometimes|required` vs `required`
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255', 'min:10'],
            'duration' => ['sometimes', 'required', 'integer', 'min:1'],
            'qualification_level_id' => ['sometimes', 'required', 'integer', 'exists:qualification_levels,id'],
            'area_id' => ['sometimes', 'required', 'integer', 'exists:areas,id'],
            'coordinator_id' => ['nullable', 'integer', 'exists:users,id', new UserHasRoleCode('COORDINADOR')],
        ];
    }

    /**
     * Mensajes de error personalizados (completos).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser una cadena de texto.',
            'name.max' => 'El :attribute no debe exceder los 100 caracteres.',

            'description.string' => 'La :attribute debe ser una cadena de texto.',
            'description.max' => 'La :attribute no debe exceder los 255 caracteres.',
            'description.min' => 'La :attribute debe tener al menos 10 caracteres.',

            'duration.required' => 'La :attribute es obligatoria.',
            'duration.integer' => 'La :attribute debe ser un número entero.',
            'duration.min' => 'La :attribute debe ser al menos 1 hora.',

            'qualification_level_id.required' => 'El :attribute es obligatorio.',
            'qualification_level_id.integer' => 'El :attribute debe ser un número entero.',
            'qualification_level_id.exists' => 'El :attribute seleccionado no existe.',

            'area_id.required' => 'El :attribute es obligatoria.',
            'area_id.integer' => 'El :attribute debe ser un número entero.',
            'area_id.exists' => 'El :attribute seleccionada no existe.',

            'coordinator_id.required' => 'El :attribute es obligatorio.',
            'coordinator_id.integer' => 'El :attribute debe ser un número entero.',
            'coordinator_id.exists' => 'El :attribute seleccionado no existe.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del programa de formación',
            'description' => 'descripción del programa de formación',
            'duration' => 'duración del programa de formación',
            'qualification_level_id' => 'nivel de formación',
            'area_id' => 'área',
            'coordinator_id' => 'coordinador',
        ];
    }
}
