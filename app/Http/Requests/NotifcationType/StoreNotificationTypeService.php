<?php

namespace App\Http\Requests\NotifcationType;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationTypeService extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'unique:notification_types,key'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The :attribute field is required.',
            'name.string' => 'The :attribute must be a string.',
            'name.max' => 'The :attribute may not be greater than :max characters.',

            'key.required' => 'The :attribute field is required.',
            'key.string' => 'The :attribute must be a string.',
            'key.max' => 'The :attribute may not be greater than :max characters.',
            'key.unique' => 'The :attribute has already been taken.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'key' => 'key',
        ];
    }
}
