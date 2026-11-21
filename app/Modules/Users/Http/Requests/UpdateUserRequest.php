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

    public function rules(): array
    {
        $id = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\- ]{7,20}$/', Rule::unique('users', 'mobile')->ignore($id)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'region_id' => ['nullable', 'integer', 'min:1'],
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
            'region_id' => $this->input('region_id') ?: null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
