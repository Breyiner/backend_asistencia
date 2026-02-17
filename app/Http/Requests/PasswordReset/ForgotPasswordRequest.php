<?php
namespace App\Http\Requests\PasswordReset;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['document_number' => 'required|string|max:20'];
    }

    public function messages(): array
    {
        return ['document_number.required' => 'El :attribute es requerido.'];
    }

    public function attributes(): array
    {
        return ['document_number' => 'número de documento'];
    }
}
