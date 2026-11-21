<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'order_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:pending,confirmed,delivered,cancelled'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'unit_price.required' => 'Unit price is required.',
            'total_price.required' => 'Total price is required.',
            'order_date.required' => 'Order date is required.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be: pending, confirmed, delivered or cancelled.',
        ];
    }
}
