<?php

namespace App\Http\Requests\QualificationLevel;

use App\Rules\AlphaSpaces;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQualificationLevelRequest extends FormRequest
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

        $qualificationLevelId = $this->route('qualification_level')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:80', "unique:qualification_levels,name,{$qualificationLevelId},id", new AlphaSpaces()],
            'description' => ['sometimes', 'nullable', 'string', 'max:255', new AlphaSpaces()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser una cadena de texto.',
            'name.min' => 'El :attribute debe tener mínimo :min caracteres.',
            'name.max' => 'El :attribute debe tener máximo :max caracteres.',
            'name.unique' => 'El :attribute ya está en uso.',

            'description.string' => 'La :attribute debe ser una cadena de texto.',
            'description.max' => 'La :attribute debe tener máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del nivel de formación',
            'description' => 'descripción del nivel de formación',
        ];
    }
}
