<?php

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / edit a person. One class for both — the only id-dependent rule is
 * the mobile uniqueness ignore, and on create there is no id.
 *
 * The assignee rules check the role as well as existence, so a telecaller id
 * cannot be posted into the MR field.
 */
class StorePersonRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            // The master decides what a client may be, so adding a type in
            // the panel is all it takes to make it postable here.
            'type' => ['required', 'string', Rule::exists('client_types', 'slug')],
            'mobile' => [
                'required', 'string', 'max:20', 'regex:/^[0-9+\- ]{7,20}$/',
                Rule::unique('persons', 'mobile')->ignore($this->route('person')),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:120'],
            'specialization' => ['nullable', 'string', 'max:150'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'assigned_mr_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Mr->value),
            ],
            'assigned_telecaller_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Telecaller->value),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'The mobile number may contain digits, spaces, + and - only.',
            'type.exists' => 'The selected client type no longer exists.',
            'assigned_mr_id.exists' => 'The selected medical rep is not a medical rep.',
            'assigned_telecaller_id.exists' => 'The selected telecaller is not a telecaller.',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'type' => $this->string('type')->value(),
            'mobile' => $this->string('mobile')->trim()->value(),
            'email' => $this->filled('email') ? $this->string('email')->trim()->lower()->value() : null,
            'address' => $this->filled('address') ? $this->string('address')->trim()->value() : null,
            'city' => $this->filled('city') ? $this->string('city')->trim()->value() : null,
            'specialization' => $this->filled('specialization') ? $this->string('specialization')->trim()->value() : null,
            'region_id' => $this->input('region_id') ?: null,
            'assigned_mr_id' => $this->input('assigned_mr_id') ?: null,
            'assigned_telecaller_id' => $this->input('assigned_telecaller_id') ?: null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
