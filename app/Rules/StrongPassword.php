<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación para contraseñas fuertes.
 *
 * Valida múltiples requisitos de seguridad configurables.
 * Acumula todos los errores y los reporta juntos, permitiendo
 * al usuario ver todos los requisitos que debe cumplir.
 *
 * Requisitos configurables:
 * - Longitud mínima (default: 8)
 * - Letra mayúscula (default: requerida)
 * - Letra minúscula (default: requerida)
 * - Número (default: requerido)
 * - Símbolo/caracter especial (default: requerido)
 * - Sin espacios en blanco (default: no permitidos)
 *
 * Uso básico (con defaults):
 * 'password' => ['required', new StrongPassword()]
 *
 * Uso personalizado:
 * 'password' => ['required', new StrongPassword(minLenght: 12, requireSymbol: false)]
 */
class StrongPassword implements ValidationRule
{
    /**
     * Longitud mínima de la contraseña.
     * @var int
     */
    protected int $minLenght;

    /**
     * Si requiere al menos una letra mayúscula.
     * @var bool
     */
    protected bool $requireUpperCase;

    /**
     * Si requiere al menos una letra minúscula.
     * @var bool
     */
    protected bool $requireLowerCase;

    /**
     * Si requiere al menos un número.
     * @var bool
     */
    protected bool $requireNumber;

    /**
     * Si requiere al menos un símbolo o caracter especial.
     * @var bool
     */
    protected bool $requireSymbol;

    /**
     * Si permite espacios en blanco.
     * false = no permite espacios (default)
     * true = permite espacios
     *
     * @var bool
     */
    protected bool $requireWhiteSpaces;

    /**
     * Crea una nueva instancia de la regla.
     *
     * @param int  $minLenght         Longitud mínima (default: 8)
     * @param bool $requireUpperCase  Requiere mayúscula (default: true)
     * @param bool $requireLowerCase  Requiere minúscula (default: true)
     * @param bool $requireNumber     Requiere número (default: true)
     * @param bool $requireSymbol     Requiere símbolo (default: true)
     * @param bool $requireWhiteSpaces Permite espacios (default: false)
     */
    public function __construct(
        int $minLenght          = 8,
        bool $requireUpperCase  = true,
        bool $requireLowerCase  = true,
        bool $requireNumber     = true,
        bool $requireSymbol     = true,
        bool $requireWhiteSpaces = false
    ) {
        $this->minLenght          = $minLenght;
        $this->requireUpperCase   = $requireUpperCase;
        $this->requireLowerCase   = $requireLowerCase;
        $this->requireNumber      = $requireNumber;
        $this->requireSymbol      = $requireSymbol;
        $this->requireWhiteSpaces = $requireWhiteSpaces;
    }

    /**
     * Ejecuta la validación de la contraseña.
     *
     * A diferencia de otras reglas, acumula TODOS los errores
     * antes de reportarlos, permitiendo mostrar todos los
     * requisitos incumplidos de una vez.
     *
     * @param string  $attribute Nombre del campo
     * @param mixed   $value     Valor de la contraseña
     * @param Closure $fail      Función para reportar errores
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Acumula todos los errores encontrados
        $errors = [];

        // Verifica longitud mínima
        if ($this->minLenght > strlen($value)) {
            $errors[] = "La contraseña debe ser de mínimo {$this->minLenght} caracteres.";
        }

        // Verifica que tenga al menos una mayúscula [A-Z]
        if ($this->requireUpperCase && !preg_match('/[A-Z]/', $value)) {
            $errors[] = 'La contraseña debe contener una letra mayúscula.';
        }

        // Verifica que tenga al menos una minúscula [a-z]
        if ($this->requireLowerCase && !preg_match('/[a-z]/', $value)) {
            $errors[] = 'La contraseña debe contener una letra minúscula.';
        }

        // Verifica que tenga al menos un número [0-9]
        if ($this->requireNumber && !preg_match('/[0-9]/', $value)) {
            $errors[] = 'La contraseña debe contener un número.';
        }

        // Verifica que tenga al menos un caracter especial
        // [^a-zA-Z0-9\s] = cualquier caracter que NO sea letra, número o espacio
        if ($this->requireSymbol && !preg_match('/[^a-zA-Z0-9\s]/', $value)) {
            $errors[] = 'La contraseña debe contener un caracter especial.';
        }

        // Verifica que NO tenga espacios (si requireWhiteSpaces es false)
        // [\s] = cualquier espacio en blanco
        if (!$this->requireWhiteSpaces && preg_match('/[\s]/', $value)) {
            $errors[] = 'La contraseña debe no contener espacios en blanco.';
        }

        // Reporta cada error individualmente
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $fail($error, null);
            }
        }
    }
}