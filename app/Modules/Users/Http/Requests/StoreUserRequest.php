<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Admin creates an MR or a telecaller. The role is never accepted from the
 * request body — it comes from the route the admin used.
 *
 * Authorization is the route's job (`permission:` middleware in
 * app/Modules/Users/Routes/web.php), so this request only validates.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\- ]{7,20}$/', 'unique:users,mobile'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
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
            'password' => $this->string('password')->value(),
            'region_ids' => array_values(array_filter((array) $this->input('region_ids', []))),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
