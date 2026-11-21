<?php

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Persons\Enums\PersonType;
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

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(PersonType::values())],
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
