<?php

namespace App\Modules\Regions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / edit a region. One class for both: the only difference is the id
 * the unique rule ignores, and on create there is none.
 *
 * Authorization is the route's job (`permission:` middleware).
 */
class StoreRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('regions', 'code')->ignore($this->route('region')),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code may contain letters, digits, hyphens and underscores only.',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'code' => $this->string('code')->trim()->upper()->value(),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
