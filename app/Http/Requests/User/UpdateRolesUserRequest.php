<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolesUserRequest extends FormRequest
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
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'distinct', 'exists:roles,id'],
        ];
    }

    /**

     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'roles.required' => 'Los :attribute son obligatorios.',
            'roles.array' => 'Los :attribute deben enviarse en formato de lista.',
            'roles.min' => 'Debes seleccionar al menos :min rol.',

            'roles.*.integer' => 'Cada :attribute seleccionado debe ser un identificador numérico.',
            'roles.*.distinct' => 'No puedes repetir roles en la selección.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'roles' => 'roles',
            'roles.*' => 'rol',
        ];
    }
}
