<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
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
        $clientId = $this->route('client')->id ?? null;

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($clientId), //validando a alteracao do email
            ],
            'phone' => 'sometimes|required|string|max:20',
            'birth_date' => 'sometimes|required|date_format:Y-m-d',
            'address' => 'sometimes|required|string|max:255',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'sometimes|required|string|max:255',
            'zip_code' => 'sometimes|required|string|max:9|regex:/^\d{5}-\d{3}$/',
        ];
    }
}
