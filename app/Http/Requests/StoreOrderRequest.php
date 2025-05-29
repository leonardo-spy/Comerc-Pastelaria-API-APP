<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'client_id' => 'required|exists:clients,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1'
            //preco sera calculado
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'É necessário ao menos um produto para o pedido.',
            'products.*.product_id.required' => 'O ID do produto é obrigatório para cada item.',
            'products.*.product_id.exists' => 'O ID do produto selecionado é inválido.',
            'products.*.quantity.required' => 'A quantidade é obrigatória para cada produto.',
            'products.*.quantity.min' => 'A quantidade para cada produto deve ser no mínimo 1.',
        ];
    }
}
