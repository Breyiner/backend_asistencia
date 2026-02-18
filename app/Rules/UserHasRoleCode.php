<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación: el usuario debe tener un rol específico.
 *
 * Verifica que el usuario identificado por el valor del campo
 * tenga asignado un rol con el código especificado.
 *
 * Uso típico: validar que el gestor asignado a una ficha
 * tenga efectivamente el rol de gestor.
 *
 * Uso:
 * 'gestor_id' => ['required', 'exists:users,id', new UserHasRoleCode('GESTOR_FICHAS')]
 *
 * @param string $roleCode Código del rol requerido (ej: 'GESTOR_FICHAS', 'INSTRUCTOR')
 */
class UserHasRoleCode implements ValidationRule
{
    /**
     * Código del rol que debe tener el usuario.
     *
     * @var string
     */
    protected string $roleCode;

    /**
     * Crea una nueva instancia de la regla.
     *
     * @param string $roleCode Código del rol requerido
     */
    public function __construct(string $roleCode)
    {
        $this->roleCode = $roleCode;
    }

    /**
     * Ejecuta la validación.
     *
     * Proceso:
     * 1. Busca el usuario por el valor del campo (ID)
     * 2. Si no existe, falla con error de usuario no encontrado
     * 3. Verifica que el usuario tenga el rol con el código requerido
     * 4. Si no tiene el rol, falla con error descriptivo
     *
     * @param string  $attribute Nombre del campo
     * @param mixed   $value     ID del usuario a validar
     * @param Closure $fail      Función para reportar error
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Busca el usuario por ID
        $user = User::query()->find($value);

        // Si el usuario no existe en la BD
        if (! $user) {
            $fail('El usuario seleccionado no existe.', null);
            return; // Detiene la validación aquí
        }

        // Verifica que el usuario tenga al menos un rol con el código requerido
        $hasRoleByCode = $user->roles()
            ->where('code', $this->roleCode)
            ->exists();

        // Si no tiene el rol requerido
        if (! $hasRoleByCode) {
            $fail("El usuario debe tener el rol con código {$this->roleCode}.", null);
        }
    }
}