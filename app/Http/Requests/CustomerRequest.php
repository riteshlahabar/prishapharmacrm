<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_code' => ['required', 'string', 'max:50'],
            'customer_email' => ['required', 'email', 'max:255', 'unique:customers,customer_email,' . $this->route('id')],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_address' => ['required', 'string', 'max:500'],
            'customer_city' => ['required', 'string', 'max:255'],
            'customer_state' => ['required', 'string', 'max:255'],
            'customer_zip' => ['required', 'string', 'max:50'],
            'customer_type' => ['required', 'string', 'in:wholesaler,retailer,distributor,hospital,clinic'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Customer name is required.',
            'customer_code.required' => 'Customer code is required.',
            'customer_email.required' => 'Email is required.',
            'customer_email.email' => 'Email must be a valid email address.',
            'customer_email.unique' => 'This email is already in use.',
            'customer_phone.required' => 'Phone is required.',
            'customer_type.in' => 'Customer type must be one of: wholesaler, retailer, distributor, hospital, clinic.',
            'status.in' => 'Status must be active or inactive.',
        ];
    }
}
