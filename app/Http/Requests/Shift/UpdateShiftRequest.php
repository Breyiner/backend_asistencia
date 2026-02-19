<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** de jornada (Shift).
 *
 * Reglas: nombre opcional (`sometimes`), único EXCLUYENDO registro actual.
 */
class UpdateShiftRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene ID del shift desde parámetros de ruta.
     */
    private function currentShiftId(): ?int
    {
        $param = $this->route('shift_id');
        return $param ? (int) $param : null;
    }

    /**
     * Reglas de validación (UPDATE).
     * 
     * `sometimes|required`: valida solo si se envía el campo.
     * `unique:tabla,columna,except_id,id_columna`: excluye registro actual.
     */
    public function rules(): array
    {
        $shiftId = $this->currentShiftId();

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                "unique:shifts,name,{$shiftId},id",
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
            'name.unique' => 'El :attribute ya está registrado.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre de la jornada',
        ];
    }
}
