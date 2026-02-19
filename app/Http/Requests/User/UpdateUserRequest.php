<?php

namespace App\Http\Requests\User;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN** de usuario.
 *
 * **Diferencias clave:** `sometimes` + unique excluyendo ID actual.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene ID del usuario desde parámetros de ruta.
     */
    private function currentUserId(): ?int
    {
        $param = $this->route('user_id');
        return $param ? (int) $param : null;
    }

    /**
     * Reglas de validación (UPDATE).
     * 
     * **Partial update:** todos campos opcionales
     * **Unique excluye actual:** `unique:tabla,campo,ID,id`
     */
    public function rules(): array
    {
        $userId = $this->currentUserId();

        return [
            'first_name' => ['sometimes', 'required', 'string', new AlphaSpaces()],
            'last_name' => ['sometimes', 'required', 'string', new AlphaSpaces()],
            'telephone_number' => ['sometimes', 'required', 'string', 'size:10', 'regex:/^\d+$/'],
            'document_type_id' => ['sometimes', 'required', 'integer', 'exists:document_types,id'],
            'document_number' => ['sometimes', 'required', 'string', 'min:6', 'max:20', "unique:users,document_number,{$userId},id"],
            'email' => ['sometimes', 'required', 'email', "unique:users,email,{$userId},id"],
            'status_id' => ['sometimes', 'required', 'integer', 'exists:user_statuses,id'],
            'roles' => ['sometimes', 'required', 'array', 'min:1'],
            'roles.*' => ['integer', 'distinct', 'exists:roles,id'],
            'area_ids' => ['sometimes', 'array'],
            'area_ids.*' => ['integer', 'distinct', 'exists:areas,id'],
        ];
    }

    /**
     * Mensajes de error personalizados (UPDATE).
     * 
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'El :attribute es obligatorio.',
            'first_name.string' => 'El :attribute debe ser texto.',
            'first_name.alpha_spaces' => 'El :attribute solo permite letras y espacios.',

            'last_name.required' => 'El :attribute es obligatorio.',
            'last_name.string' => 'El :attribute debe ser texto.',
            'last_name.alpha_spaces' => 'El :attribute solo permite letras y espacios.',

            'telephone_number.required' => 'El :attribute es obligatorio.',
            'telephone_number.string' => 'El :attribute debe ser texto.',
            'telephone_number.size' => 'El :attribute debe tener exactamente :size dígitos.',
            'telephone_number.regex' => 'El :attribute solo puede contener números.',

            'document_type_id.required' => 'El :attribute es obligatorio.',
            'document_type_id.integer' => 'El :attribute debe ser un número entero.',
            'document_type_id.exists' => 'El :attribute no existe.',

            'document_number.required' => 'El :attribute es obligatorio.',
            'document_number.string' => 'El :attribute debe ser texto.',
            'document_number.min' => 'El :attribute debe tener al menos :min caracteres.',
            'document_number.max' => 'El :attribute no debe tener más de :max caracteres.',
            'document_number.unique' => 'Este :attribute ya está registrado en el sistema.',

            'email.required' => 'El :attribute es obligatorio.',
            'email.email' => 'El :attribute debe tener formato válido.',
            'email.unique' => 'Este :attribute ya está registrado en el sistema.',

            'status_id.required' => 'El :attribute es obligatorio.',
            'status_id.integer' => 'El :attribute debe ser un número entero.',
            'status_id.exists' => 'El :attribute no existe.',

            'roles.required' => 'Los :attribute son obligatorios.',
            'roles.array' => 'Los :attribute deben enviarse en formato de lista.',
            'roles.min' => 'Debes seleccionar al menos :min rol.',
            'roles.*.integer' => 'Cada :attribute seleccionado debe ser un identificador numérico.',
            'roles.*.distinct' => 'No puedes repetir roles en la selección.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',

            'area_ids.array' => 'Las :attribute deben enviarse en formato de lista.',
            'area_ids.*.integer' => 'Cada :attribute seleccionada debe ser un identificador numérico.',
            'area_ids.*.distinct' => 'No puedes repetir áreas en la selección.',
            'area_ids.*.exists' => 'Una de las áreas seleccionadas no existe.',
        ];
    }

    /**
     * Atributos legibles en mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'telephone_number' => 'teléfono',
            'email' => 'correo',
            'document_type_id' => 'tipo de documento',
            'document_number' => 'número de documento',
            'status_id' => 'estado',
            'roles' => 'roles',
            'roles.*' => 'rol',
            'area_ids' => 'áreas',
            'area_ids.*' => 'área',
        ];
    }
}
