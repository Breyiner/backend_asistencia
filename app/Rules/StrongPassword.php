<?php


namespace App\Rules;


use Closure;
use Illuminate\Contracts\Validation\ValidationRule;


class StrongPassword implements ValidationRule
{


    protected int $minLenght;
    protected bool $requireUpperCase;
    protected bool $requireLowerCase;
    protected bool $requireNumber;
    protected bool $requireSymbol;
    protected bool $requireWhiteSpaces;


    public function __construct(
        int $minLenght = 8,
        bool $requireUpperCase = true,
        bool $requireLowerCase = true,
        bool $requireNumber = true,
        bool $requireSymbol = true,
        bool $requireWhiteSpaces = false
    ) {
        $this->minLenght = $minLenght;
        $this->requireUpperCase = $requireUpperCase;
        $this->requireLowerCase = $requireLowerCase;
        $this->requireNumber = $requireNumber;
        $this->requireSymbol = $requireSymbol;
        $this->requireWhiteSpaces = $requireWhiteSpaces;
    }


    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errors = [];


        if ($this->minLenght > strlen($value)) {
            $errors[] = "La contraseña debe ser de mínimo {$this->minLenght} caracteres.";
        }


        if ($this->requireUpperCase && !preg_match('/[A-Z]/', $value)) {
            $errors[] = 'La contraseña debe contener una letra mayúscula.';
        }


        if ($this->requireLowerCase && !preg_match('/[a-z]/', $value)) {
            $errors[] = 'La contraseña debe contener una letra minúscula.';
        }


        if ($this->requireNumber && !preg_match('/[0-9]/', $value)) {
            $errors[] = 'La contraseña debe contener un número.';
        }


        if ($this->requireSymbol && !preg_match('/[^a-zA-Z0-9\s]/', $value)) {
            $errors[] = 'La contraseña debe contener un caracter especial.';
        }


        if (!$this->requireWhiteSpaces && preg_match('/[\s]/', $value)) {
            $errors[] = 'La contraseña debe no contener espacios en blanco.';
        }


        if (!empty($errors)) {
            foreach ($errors as $error) {
                $fail($error, null);
            }
        }
    }
}
