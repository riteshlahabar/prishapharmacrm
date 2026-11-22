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

    /**
     * Upper-case the code *before* validating, not only in `payload()`.
     *
     * The column stores the upper-cased form, so validating the raw input left
     * `unique` checking a different string than the one that gets inserted:
     * on a case-sensitive collation "dup" passed the rule and then hit the
     * unique index as "DUP" — a 500 instead of a field error. MySQL's default
     * collation hid it; the test suite's sqlite did not.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => $this->string('code')->trim()->upper()->value()]);
        }
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
