<?php

namespace App\Http\Requests\Role;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role_id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:50',
                new AlphaSpaces(),
                Rule::unique('roles', 'name')->ignore($roleId),
            ],

            'code' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('roles', 'code')->ignore($roleId),
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser en formato de texto.',
            'name.min' => 'El :attribute debe tener al menos :min caracteres.',
            'name.max' => 'El :attribute no debe tener más de :max caracteres.',
            'name.unique' => 'El :attribute ya existe.',

            'code.required' => 'El código del rol es obligatorio.',
            'code.string' => 'El código del rol debe ser en formato de texto.',
            'code.min' => 'El código del rol debe tener al menos :min caracteres.',
            'code.max' => 'El código del rol no debe tener más de :max caracteres.',
            'code.unique' => 'El código del rol ya existe.',

            'description.string' => 'La :attribute debe ser en formato de texto.',
            'description.min' => 'La :attribute debe tener al menos :min caracteres.',
            'description.max' => 'La :attribute no debe tener más de :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del rol',
            'code' => 'código del rol',
            'description' => 'descripción',
        ];
    }
}