<?php

namespace App\Http\Requests\UserProfile;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **ACTUALIZACIÓN PERFIL** usuario propio.
 *
 * Campos opcionales para partial updates del perfil básico.
 */
class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios (perfil propio).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación (PERFIL).
     * 
     * **Teléfono Colombia:** exactamente 10 dígitos
     * **Nombres:** solo letras + espacios (AlphaSpaces)
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', new AlphaSpaces()],
            'last_name' => ['sometimes', 'required', 'string', new AlphaSpaces()],
            'telephone_number' => ['sometimes', 'required', 'string', 'size:10', 'regex:/^\d+$/'],
        ];
    }

    /**
     * Mensajes de error personalizados (completos).
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
        ];
    }
}
