<?php

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add Client, from either app. Separate from `StorePersonRequest` (the
 * panel's) because the apps may decide less: an admin fills the whole row,
 * an MR or telecaller fills the contact and names the counterpart.
 *
 * What the body may NOT carry:
 *  - the caller's own assignment — it comes from the token, like `mr_id` on
 *    a visit and `telecaller_id` on a call log;
 *  - `region_id` — derived in the service, so a device cannot file a client
 *    into a territory to make it visible;
 *  - `is_active` — a freshly added client is active.
 * Each is `prohibited` rather than ignored, so a client sending one gets a
 * 422 naming the field instead of silently having it dropped.
 *
 * The counterpart assignment is **required**: every client added from an app
 * has both an MR and a telecaller from the moment it exists.
 */
class StoreAppPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route's ability middleware has already settled which app this
        // is; PersonService settles what that app may write.
        return true;
    }

    /** Lower-case the email before `unique` sees it — see StorePersonRequest. */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => $this->string('email')->trim()->lower()->value()]);
        }
    }

    public function rules(): array
    {
        $isMr = (bool) $this->user()?->isMr();

        return [
            'name' => ['required', 'string', 'max:255'],
            // Active types only: the panel's form may still offer a retired
            // type to a client already carrying it, but nothing new gets one.
            'type' => [
                'required', 'string',
                Rule::exists('client_types', 'slug')->where('is_active', true),
            ],
            'mobile' => [
                'required', 'string', 'max:20', 'regex:/^[0-9+\- ]{7,20}$/',
                Rule::unique('persons', 'mobile'),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:120'],
            'specialization' => ['nullable', 'string', 'max:150'],

            // The counterpart: required for the app that must name one,
            // refused outright for the app whose own id it is.
            'assigned_mr_id' => $isMr
                ? ['prohibited']
                : [
                    'required', 'integer',
                    Rule::exists('users', 'id')
                        ->where('role', UserRole::Mr->value)
                        ->where('is_active', true),
                ],
            'assigned_telecaller_id' => $isMr
                ? [
                    'required', 'integer',
                    Rule::exists('users', 'id')
                        ->where('role', UserRole::Telecaller->value)
                        ->where('is_active', true),
                ]
                : ['prohibited'],

            'region_id' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'The mobile number may contain digits, spaces, + and - only.',
            'mobile.unique' => 'A client with this mobile number already exists.',
            'type.exists' => 'Choose a client type from the list.',
            'assigned_mr_id.required' => 'Choose the medical rep this client belongs to.',
            'assigned_mr_id.exists' => 'The selected medical rep is not an active medical rep.',
            'assigned_mr_id.prohibited' => 'You are the medical rep on a client you add.',
            'assigned_telecaller_id.required' => 'Choose the telecaller this client belongs to.',
            'assigned_telecaller_id.exists' => 'The selected telecaller is not an active telecaller.',
            'assigned_telecaller_id.prohibited' => 'You are the telecaller on a client you add.',
            'region_id.prohibited' => 'The region is set from the assigned rep, not from the app.',
            'is_active.prohibited' => 'A new client is always active.',
        ];
    }

    /**
     * The fields the body is allowed to decide. The caller's own assignment,
     * the region and `is_active` are added by `PersonService::createForApp`.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'type' => $this->string('type')->value(),
            'mobile' => $this->string('mobile')->trim()->value(),
            'email' => $this->filled('email') ? $this->string('email')->trim()->lower()->value() : null,
            'address' => $this->filled('address') ? $this->string('address')->trim()->value() : null,
            'city' => $this->filled('city') ? $this->string('city')->trim()->value() : null,
            'specialization' => $this->filled('specialization')
                ? $this->string('specialization')->trim()->value()
                : null,
            'assigned_mr_id' => $this->input('assigned_mr_id') ?: null,
            'assigned_telecaller_id' => $this->input('assigned_telecaller_id') ?: null,
        ];
    }
}
