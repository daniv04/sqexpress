<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'min:7', 'max:20'],
            'cedula' => ['nullable', 'string', 'max:50'],
                        'provincia_id' => ['nullable', 'exists:provincias,id'],
                        'canton_id' => ['nullable', 'exists:cantones,id'],
                        'distrito_id' => ['nullable', 'exists:distritos,id'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
