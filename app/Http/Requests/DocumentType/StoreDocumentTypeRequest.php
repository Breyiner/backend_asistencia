<?php

namespace App\Http\Requests\DocumentType;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación **CREACIÓN** tipo documento (CC, TI, CE).
 *
 * Reglas: name/acronym únicos + AlphaSpaces + uppercase.
 */
class StoreDocumentTypeRequest extends FormRequest
{
    /**
     * Autoriza todos los usuarios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas validación completa (CREATE).
     */
    public function rules(): array
    {
        return [
            'name' => ['required', new AlphaSpaces(), 'string', 'min:10', 'max:50', 'unique:document_types,name'],
            'acronym' => ['required', 'alpha', 'uppercase', 'string', 'min:2', 'max:3', 'unique:document_types,acronym'],
        ];
    }

    /**
     * Mensajes error personalizados (español).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.min' => 'El :attribute debe tener mínimo :min caracteres.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.unique' => 'El :attribute ya existe.',
            'acronym.required' => 'El :attribute es obligatorio.',
            'acronym.alpha' => 'El :attribute debe tener solo letras.',
            'acronym.uppercase' => 'El :attribute debe estar en mayúsculas.',
            'acronym.string' => 'El :attribute debe ser texto.',
            'acronym.min' => 'El :attribute debe tener mínimo :min caracteres.',
            'acronym.max' => 'El :attribute no puede exceder :max caracteres.',
            'acronym.unique' => 'El :attribute ya existe.',
        ];
    }

    /**
     * Atributos legibles en errores.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del tipo de documento',
            'acronym' => 'acrónimo del tipo de documento',
        ];
    }
}
