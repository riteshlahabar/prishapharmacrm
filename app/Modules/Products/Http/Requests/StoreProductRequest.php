<?php

namespace App\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / edit a product. One class for both; the only id-dependent rule is
 * the name uniqueness ignore, and on create there is no id.
 *
 * Authorization is the route's job (`permission:` middleware).
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:150',
                // Two products with one name make the app dropdown a guess.
                Rule::unique('products', 'name')->ignore($this->route('product')),
            ],
            'box_qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'cost' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A product with that name already exists.',
            'box_qty.min' => 'Stock cannot be negative.',
            'cost.min' => 'A cost cannot be negative.',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'box_qty' => (int) $this->input('box_qty'),
            'cost' => (float) $this->input('cost'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
