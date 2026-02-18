<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación: solo letras y espacios.
 *
 * Valida que un campo contenga únicamente letras Unicode
 * y espacios en blanco. Acepta letras con acentos y caracteres
 * de otros idiomas (ñ, á, é, etc.).
 *
 * Uso típico: validar nombres y apellidos.
 *
 * Uso en reglas:
 * 'nombre' => ['required', 'string', new AlphaSpaces()]
 *
 * Uso en imports:
 * 'nombres' => ['required', 'string', new AlphaSpaces()]
 */
class AlphaSpaces implements ValidationRule
{
    /**
     * Ejecuta la regla de validación.
     *
     * Usa expresión regular con soporte Unicode (\pL = cualquier letra
     * de cualquier idioma, \s = espacios, tabuladores, etc.).
     *
     * El modificador /u activa el modo Unicode para que \pL funcione.
     *
     * @param string  $attribute Nombre del campo siendo validado
     * @param mixed   $value     Valor del campo
     * @param Closure $fail      Función a llamar si la validación falla
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // \pL: cualquier letra Unicode (a-z, A-Z, á, é, ñ, etc.)
        // \s: cualquier espacio en blanco
        // ^...+$: toda la cadena debe cumplir el patrón
        // /u: modo Unicode
        if (! preg_match('/^[\pL\s]+$/u', $value)) {
            $fail('El campo :attribute solo puede contener letras y espacios.', null);
        }
    }
}