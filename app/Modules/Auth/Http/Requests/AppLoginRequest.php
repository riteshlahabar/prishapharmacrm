<?php

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Login from either Flutter app. `role` is optional but recommended: when the
 * app sends the role it serves, the server refuses a user from the other app
 * instead of handing out a token the app would then have to reject.
 */
class AppLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', Rule::in([UserRole::Mr->value, UserRole::Telecaller->value])],
            'device_name' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->trim()->lower()->value();
    }

    public function expectedRole(): ?UserRole
    {
        return $this->filled('role')
            ? UserRole::from($this->string('role')->value())
            : null;
    }

    public function deviceName(): string
    {
        return $this->filled('device_name')
            ? $this->string('device_name')->trim()->value()
            : 'mobile';
    }
}
