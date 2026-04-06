<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePhoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'nullable',
                'string',
                'regex:/^[0-9\s\-\+\(\)]+$/',
                'min:7',
                'max:20',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'El número de teléfono contiene caracteres inválidos.',
            'phone.min' => 'El número de teléfono debe tener al menos 7 dígitos.',
            'phone.max' => 'El número de teléfono no puede exceder 20 caracteres.',
        ];
    }
}
