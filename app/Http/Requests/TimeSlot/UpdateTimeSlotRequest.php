<?php

namespace App\Http\Requests\TimeSlot;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('time_slot_id');

        return [
            'code' => ['sometimes', 'required', 'string', 'min:3', 'max:30', 'regex:/^[A-Z_]+$/', "unique:time_slots,code,{$id}"],
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:30'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El :attribute es obligatorio.',
            'code.regex' => 'El :attribute debe estar en mayúsculas y usar solo letras y guiones bajos (Ej: MORNING).',
            'code.unique' => 'El :attribute ya existe.',

            'name.required' => 'El :attribute es obligatorio.',

            'start_time.date_format' => 'La :attribute debe tener el formato HH:MM (24h).',
            'end_time.after' => 'La hora final debe ser mayor que la hora inicial.',
        ];
    }

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