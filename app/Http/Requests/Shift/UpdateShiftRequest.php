<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $shiftId = $this->route('shift_id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                "unique:shifts,name,{$shiftId},id" ,
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
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.unique' => 'El :attribute ya está registrado.',
            
            'start_time.required' => 'La :attribute es obligatoria.',
            'start_time.date_format' => 'La :attribute debe tener formato HH:MM (24h).',

            'end_time.required' => 'La :attribute es obligatoria.',
            'end_time.date_format' => 'La :attribute debe tener formato HH:MM (24h).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre de la jornada',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
        ];
    }
}
