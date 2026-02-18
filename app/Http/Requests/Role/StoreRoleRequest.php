<?php

namespace App\Http\Requests\Role;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación **CREACIÓN** rol (Spatie) para guard web.
 *
 * Reglas: name AlphaSpaces único por guard web + code en MAYÚSCULAS/guion_bajo único por guard web.
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (CREATE).
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                new AlphaSpaces(),
                'string',
                'min:3',
                'max:50',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'code' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('roles', 'code')->where('guard_name', 'web'),
            ],
            'description' => ['nullable', 'string', 'min:10', 'max:255', new AlphaSpaces()],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser en formato de texto.',
            'name.min' => 'El :attribute debe tener al menos :min caracteres.',
            'name.max' => 'El :attribute no debe tener más de :max caracteres.',
            'name.unique' => 'El :attribute ya existe para este guard.',
            'code.required' => 'El :attribute es obligatorio.',
            'code.string' => 'El :attribute debe ser en formato de texto.',
            'code.min' => 'El :attribute debe tener al menos :min caracteres.',
            'code.max' => 'El :attribute no debe tener más de :max caracteres.',
            'code.regex' => 'El :attribute solo puede contener letras mayúsculas, números y guion bajo.',
            'code.unique' => 'El :attribute ya existe para este guard.',
            'description.string' => 'La :attribute debe ser en formato de texto.',
            'description.min' => 'La :attribute debe tener al menos :min caracteres.',
            'description.max' => 'La :attribute no debe tener más de :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del rol',
            'code' => 'código del rol',
            'description' => 'descripción',
        ];
    }
}
