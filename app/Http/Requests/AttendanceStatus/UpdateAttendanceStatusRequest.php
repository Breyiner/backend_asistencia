<?php

namespace App\Http\Requests\AttendanceStatus;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('attendance_status_id');

        return [
            'code' => ['required', 'string', 'max:50', "unique:attendance_statuses,code,{$id}"],
            'name' => ['required', 'string', 'max:50', "unique:attendance_statuses,name,{$id}"],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El :attribute es obligatorio.',
            'code.string' => 'El :attribute debe ser texto.',
            'code.max' => 'El :attribute no puede exceder :max caracteres.',
            'code.unique' => 'El :attribute ya existe.',

            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.unique' => 'El :attribute ya existe.',

            'description.string' => 'La :attribute debe ser texto.',
            'description.max' => 'La :attribute no puede exceder :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código del estado de asistencia',
            'name' => 'nombre del estado de asistencia',
            'description' => 'descripción del estado',
        ];
    }
}