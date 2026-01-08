<?php

namespace App\Http\Requests\ClassType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('class_type_id');
        
        return [
            'name' => ['required', 'string', 'max:50', "unique:class_types,name,{$id}"],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe ser texto.',
            'name.max' => 'El :attribute no puede exceder 50 caracteres.',
            'name.unique' => 'El :attribute ya existe.',
            'description.string' => 'El :attribute debe ser texto.',
            'description.max' => 'El :attribute no puede exceder 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del tipo de clase',
            'description' => 'descripción del tipo de clase',
        ];
    }
}
