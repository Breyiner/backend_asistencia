<?php

namespace App\Http\Requests\Term;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** de trimestre (Term).
 *
 * **Correcciones aplicadas:**
 * - Regex estricto: `[1-7]`
 * - Método privado para ID reusable
 * - Punto final en mensaje unique
 */
class UpdateTermRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene ID del trimestre desde parámetros de ruta.
     */
    private function currentTermId(): ?int
    {
        $param = $this->route('term_id');
        return $param ? (int) $param : null;
    }

    /**
     * Reglas de validación (UPDATE).
     * 
     * Campo opcional con **unique ignorando registro actual**.
     * Mismo formato estricto que CREATE.
     */
    public function rules(): array
    {
        $termId = $this->currentTermId();

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^Trimestre [1-7]$/i',
                "unique:terms,name,{$termId},id",
            ],
        ];
    }

    /**
     * Mensajes de error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.regex' => 'El :attribute debe seguir el formato "Trimestre X" (X=1-4).',
            'name.unique' => 'El :attribute ya existe.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del trimestre',
        ];
    }
}
