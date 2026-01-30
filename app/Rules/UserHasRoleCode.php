<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserHasRoleCode implements ValidationRule
{
    protected string $roleCode;

    public function __construct(string $roleCode)
    {
        $this->roleCode = $roleCode;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::query()->find($value);

        if (! $user) {
            $fail('El usuario seleccionado no existe.', null);
            return;
        }

        $hasRoleByCode = $user->roles()
            ->where('code', $this->roleCode)
            ->exists();

        if (! $hasRoleByCode) {
            $fail("El usuario debe tener el rol con código {$this->roleCode}.", null);
        }
    }
}