<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AlphaSpaces implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[\\pL\\s]+$/u', $value)) {
            $fail('El campo :attribute solo puede contener letras y espacios.', null);
        }
    }
}