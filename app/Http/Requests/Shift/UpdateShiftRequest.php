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
        $time_slot_id = $this->route('time_slot_id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                "unique:time_slots,name,{$time_slot_id},id" ,
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
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre de la jornada',
        ];
    }
}
