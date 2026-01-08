<?php

namespace App\Http\Requests\AttendanceStatus;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceStatusRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:50', 'unique:attendance_statuses,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
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
            'name' => 'nombre del estado de asistencia',
            'description' => 'descripción del estado',
        ];
    }
}
