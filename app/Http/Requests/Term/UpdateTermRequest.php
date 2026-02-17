<?php

namespace App\Http\Requests\Term;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTermRequest extends FormRequest
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
        $termId = $this->route('term_id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^Trimestre [1-9]$/i', "unique:terms,name,{$termId},id"],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder :max caracteres.',
            'name.regex' => 'El :attribute debe seguir el formato "Trimestre X" (X=1-4).',
            'name.unique' => 'El :attribute ya existe'
        ];
    }


    public function attributes(): array
    {
        return [
            'name' => 'nombre del trimestre',
        ];
    }
}
