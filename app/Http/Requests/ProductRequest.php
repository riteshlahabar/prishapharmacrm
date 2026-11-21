<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'product_name' => ['required', 'string', 'max:255'],
            'product_code' => ['required', 'string', 'max:100', 'unique:products,product_code,' . $this->route('id')],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'mrp' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach ($rules as $key => $value) {
                $rules[$key] = ['sometimes'] + $value;
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'Product name is required.',
            'product_code.required' => 'Product code is required.',
            'product_code.unique' => 'This product code is already in use.',
            'mrp.required' => 'MRP is required.',
            'selling_price.required' => 'Selling price is required.',
            'stock_quantity.required' => 'Stock quantity is required.',
        ];
    }
}
