<?php

namespace App\Http\Requests\DocumentType;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

class PartialUpdateDocumentTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $documentType = $this->route('documentType_id');

        return [
            'name' => ['sometimes', new AlphaSpaces(), 'string', 'min:10', 'max:50', "unique:document_types,name,{$documentType},id"],
            'acronym' => ['required', 'alpha', 'uppercase', 'string', 'min:2', 'max:3', "unique:document_types,acronym,{$documentType},id"],
        ];
    }

    public function messages()
    {
        return [
            'name.string' => 'El :attribute debe tener solo caracteres de tipo texto.',
            'name.unique' => 'El :attribute ya existe.',
            'name.max' => 'El :attribute tiene maximo :max caracteres.',
            
            'acronym.alpha' => 'El :attribute debe tener solo letras.',
            'acronym.uppercase' => 'El :attribute debe estar en mayusculas.',
            'acronym.string' => 'El :attribute debe tener solo caracteres de tipo texto.',
            'acronym.unique' => 'El :attribute ya existe.',
            'acronym.max' => 'El :attribute tiene maximo :max caracteres.',
            'acronym.min' => 'El :attribute tiene minimo :min caracteres.'
        ];
    }
    public function attributes()
    {
        return [
            'name' => 'nombre del tipo de documento',
            'acronym' => 'acronimo del tipo de documento'
        ];
    }
}
