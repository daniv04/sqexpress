<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
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
            'tracking' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('packages')->where(function ($query) {
                    return $query->where('shipping_method_id', $this->shipping_method_id);
                }),
            ],
            'shipping_method_id' => [
                'required',
                'exists:shipping_methods,id',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'weight' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:999.99',
            ],
            'approx_value' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:99999.99',
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
            'tracking.required' => 'El número de tracking es obligatorio.',
            'tracking.unique' => 'Este número de tracking ya existe para el método de envío seleccionado.',
            'shipping_method_id.required' => 'Debe seleccionar un método de envío.',
            'shipping_method_id.exists' => 'El método de envío seleccionado no es válido.',
            'weight.numeric' => 'El peso debe ser un número.',
            'weight.min' => 'El peso debe ser mayor a 0.',
            'approx_value.numeric' => 'El valor aproximado debe ser un número.',
            'approx_value.min' => 'El valor aproximado debe ser mayor a 0.',
        ];
    }
}
