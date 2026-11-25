<?php

namespace App\Modules\Persons\Http\Requests;

use App\Modules\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing a client from the MR or telecaller app.
 *
 * The sibling of `StoreAppPersonRequest`, and it refuses the same four
 * things for the same reasons — your own assignment, the other app's
 * counterpart, `region_id` and `is_active` — with two differences that come
 * from this being a PATCH:
 *
 *  - every field is `sometimes`, so the app may send only what the rep
 *    actually changed and an absent key means "leave it alone". A key sent
 *    as null *does* clear the column, which is how an optional field like
 *    the email is emptied;
 *  - `mobile` is unique *ignoring this client*, so saving the form without
 *    touching the number is not a 422 against the row being edited.
 *
 * Handing the client to a different counterpart stays allowed — that is the
 * one assignment this app owns — but moving your own side is not: an MR
 * cannot push a client onto another rep from the phone, because the client
 * would then vanish from the list they are standing in.
 */
class UpdateAppPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route's ability middleware settled which app this is;
        // PersonService::updateForApp settles whether this caller is an
        // assignee on the row, and reports a stranger's client as missing.
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
        $person = $this->route('person');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            // Active types only, as on the store request: a retired type may
            // stay on a client already carrying it, but nothing moves onto one.
            'type' => [
                'sometimes', 'required', 'string',
                Rule::exists('client_types', 'slug')->where('is_active', true),
            ],
            'mobile' => [
                'sometimes', 'required', 'string', 'max:20', 'regex:/^[0-9+\- ]{7,20}$/',
                Rule::unique('persons', 'mobile')->ignore($person),
            ],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:150'],

            'assigned_mr_id' => $isMr
                ? ['prohibited']
                : [
                    'sometimes', 'nullable', 'integer',
                    Rule::exists('users', 'id')
                        ->where('role', UserRole::Mr->value)
                        ->where('is_active', true),
                ],
            'assigned_telecaller_id' => $isMr
                ? [
                    'sometimes', 'nullable', 'integer',
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
            'assigned_mr_id.exists' => 'The selected medical rep is not an active medical rep.',
            'assigned_mr_id.prohibited' => 'You cannot move your own client to another medical rep.',
            'assigned_telecaller_id.exists' => 'The selected telecaller is not an active telecaller.',
            'assigned_telecaller_id.prohibited' => 'You cannot move your own client to another telecaller.',
            'region_id.prohibited' => 'The region follows the assigned rep, not the app.',
            'is_active.prohibited' => 'Deactivating a client is panel work.',
        ];
    }

    /**
     * Only the keys the body actually carried, trimmed. Absent means
     * unchanged, so this cannot be built the way `StoreAppPersonRequest`
     * builds its payload — a `?? null` there would quietly wipe a column here.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = [];

        foreach (['name', 'type', 'mobile', 'email', 'address', 'city', 'specialization'] as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $value = $this->input($key);
            $value = is_string($value) ? trim($value) : $value;
            // '' and null say the same thing about an optional column, and
            // the column is nullable: store the null.
            $data[$key] = ($value === '' || $value === null) ? null : $value;
        }

        foreach (['assigned_mr_id', 'assigned_telecaller_id'] as $key) {
            if ($this->has($key)) {
                $data[$key] = $this->input($key) ?: null;
            }
        }

        return $data;
    }
}
