<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserHasRole implements ValidationRule
{

    protected string $roleName;

    public function __construct(string $roleName)
    {
        $this->roleName = $roleName;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $user = User::find($value);

        if (! $user) {
            $fail('El gestor seleccionado no existe.', null);
            return;
        }

        if (! $user->hasRole($this->roleName)) {
            $fail("El usuario debe tener el rol de {$this->roleName}.", null);
        }
    }
}
