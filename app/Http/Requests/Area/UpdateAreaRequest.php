<?php

namespace App\Http\Requests\Area;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación **ACTUALIZACIÓN** área (parcial).
 *
 * Reglas: sometimes + unique ignore ID (Rule::unique).
 */
class UpdateAreaRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación parcial + ignore ID (UPDATE).
     */
    public function rules(): array
    {
        $areaId = $this->route('area_id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:5',
                'max:100',
                new AlphaSpaces(),
                Rule::unique('areas', 'name')->ignore($areaId),
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:200',
                new AlphaSpaces(),
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
            'name.min' => 'El :attribute debe tener mínimo :min caracteres.',
            'name.max' => 'El :attribute debe tener máximo :max caracteres.',
            'name.unique' => 'El :attribute ya está en uso.',
            'description.string' => 'La :attribute debe ser texto.',
            'description.min' => 'La :attribute debe tener mínimo :min caracteres.',
            'description.max' => 'La :attribute debe tener máximo :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del área',
            'description' => 'descripción del área',
        ];
    }
}
