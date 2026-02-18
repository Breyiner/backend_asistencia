<?php

namespace App\Http\Requests\QualificationLevel;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** nivel de calificación SENA.
 *
 * Reglas: sometimes + nombre único ignorando registro actual.
 */
class UpdateQualificationLevelRequest extends FormRequest
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
        $qualificationLevelId = $this->route('qualification_level');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:80', "unique:qualification_levels,name,{$qualificationLevelId},id", new AlphaSpaces()],
            'description' => ['sometimes', 'nullable', 'string', 'max:255', new AlphaSpaces()],
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
            'name.min' => 'El :attribute debe tener mínimo :min caracteres.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.unique' => 'El :attribute ya está en uso.',
            'description.string' => 'La :attribute debe ser texto.',
            'description.max' => 'La :attribute no puede exceder :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del nivel de formación',
            'description' => 'descripción del nivel de formación',
        ];
    }
}
