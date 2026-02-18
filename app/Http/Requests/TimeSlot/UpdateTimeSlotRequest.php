<?php

namespace App\Http\Requests\TimeSlot;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** de franja horaria (TimeSlot).
 *
 * **Correcciones:**
 * - `unique:time_slots,code,{$id}` → `unique:time_slots,code,{$id},id`
 * - Mensajes completos agregados
 * - Método privado para ID
 */
class UpdateTimeSlotRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene ID de la franja horaria desde ruta.
     */
    private function currentTimeSlotId(): ?int
    {
        $param = $this->route('time_slot_id');
        return $param ? (int) $param : null;
    }

    /**
     * Reglas de validación (UPDATE).
     * 
     * Campos opcionales (`sometimes`), unique excluyendo actual.
     */
    public function rules(): array
    {
        $timeSlotId = $this->currentTimeSlotId();

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[A-Z_]+$/',
                "unique:time_slots,code,{$timeSlotId},id"  // ✅ Corregido
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:30'
            ],
            'start_time' => [
                'sometimes',
                'required',
                'date_format:H:i'
            ],
            'end_time' => [
                'sometimes',
                'required',
                'date_format:H:i',
                'after:start_time'
            ],
        ];
    }

    /**
     * Mensajes de error personalizados (español).
     * 
     * **Completos** (agregados los faltantes).
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El :attribute es obligatorio.',
            'code.regex' => 'El :attribute debe estar en mayúsculas y usar solo letras y guiones bajos (Ej: MORNING).',
            'code.unique' => 'El :attribute ya existe.',

            'name.required' => 'El :attribute es obligatorio.',

            'start_time.required' => 'La :attribute es obligatoria.',      // ✅ Agregado
            'start_time.date_format' => 'La :attribute debe tener el formato HH:MM (24h).',

            'end_time.required' => 'La :attribute es obligatoria.',        // ✅ Agregado
            'end_time.date_format' => 'La :attribute debe tener el formato HH:MM (24h).',
            'end_time.after' => 'La hora final debe ser mayor que la hora inicial.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'start_time' => 'hora inicial',
            'end_time' => 'hora final',
        ];
    }
}
