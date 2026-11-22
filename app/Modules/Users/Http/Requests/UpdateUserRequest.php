<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Edit of an existing MR or telecaller. A blank password field means "keep the
 * current password" — the service only writes it when it is filled in.
 *
 * Both resource routes name their parameter `user`, so the ignore rule below
 * works for MRs and telecallers alike.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Lower-case the email *before* validating, not only in `payload()`.
     *
     * The column stores the lower-cased form, so validating the raw input left
     * `unique` checking a different string than the one that gets inserted: on
     * a case-sensitive collation "TAKEN@Example.com" passed the rule and then
     * hit the unique index as "taken@example.com" — a 500 instead of a field
     * error. (Padding needs no handling here: Laravel's TrimStrings middleware
     * has already trimmed every input by this point.)
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => $this->string('email')->trim()->lower()->value()]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\- ]{7,20}$/', Rule::unique('users', 'mobile')->ignore($id)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            // One id for an MR, many for a telecaller; the Regions module
            // decides which shape applies to the role being saved.
            'region_ids' => ['nullable', 'array'],
            'region_ids.*' => ['integer', 'exists:regions,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'The mobile number may contain digits, spaces, + and - only.',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'email' => $this->string('email')->trim()->lower()->value(),
            'mobile' => $this->string('mobile')->trim()->value(),
            'password' => $this->filled('password') ? $this->string('password')->value() : null,
            'region_ids' => array_values(array_filter((array) $this->input('region_ids', []))),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
