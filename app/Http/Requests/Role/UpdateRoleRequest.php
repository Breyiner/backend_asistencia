<?php

namespace App\Http\Requests\Role;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
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

        $roleId = $this->route('role_id');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:50', new AlphaSpaces(), 'unique:roles,name,{role_id},id'],
            'description' => ['sometimes', 'nullable', 'string', 'min:10', 'max:255', new AlphaSpaces()],

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

            'description.string' => 'La :attribute debe ser en formato de texto.',
            'description.min' => 'La :attribute debe tener al menos :min caracteres.',
            'description.max'    => 'La :attribute no debe tener más de :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del rol',
            'description' => 'descripción',
        ];
    }
}
