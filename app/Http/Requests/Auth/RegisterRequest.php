<?php

namespace App\Http\Requests\Auth;

use App\Rules\AlphaSpaces;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **REGISTRO** usuario SENA completo.
 *
 * Reglas: StrongPassword + document_number/email únicos + AlphaSpaces nombres.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación registro completo.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', new AlphaSpaces()],
            'last_name' => ['required', 'string', new AlphaSpaces()],
            'telephone_number' => ['required', 'string', 'size:10', 'regex:/^\d+$/'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'document_number' => ['required', 'string', 'min:6', 'max:20', 'unique:users,document_number'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'max:50', new StrongPassword()],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'El :attribute es obligatorio.',
            'first_name.string' => 'El :attribute debe ser texto.',
            'last_name.required' => 'El :attribute es obligatorio.',
            'last_name.string' => 'El :attribute debe ser texto.',
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
            'document_number.unique' => 'Este :attribute ya está registrado.',
            'email.required' => 'El :attribute es obligatorio.',
            'email.email' => 'El :attribute debe tener formato válido.',
            'email.unique' => 'Este :attribute ya está registrado.',
            'password.required' => 'La :attribute es obligatoria.',
            'password.string' => 'La :attribute debe ser texto.',
            'password.max' => 'La :attribute no debe tener más de :max caracteres.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'telephone_number' => 'teléfono',
            'email' => 'correo',
            'password' => 'contraseña',
            'document_type_id' => 'tipo de documento',
            'document_number' => 'número de documento',
        ];
    }
}
