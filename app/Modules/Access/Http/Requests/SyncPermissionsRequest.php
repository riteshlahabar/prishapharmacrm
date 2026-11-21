<?php

namespace App\Modules\Access\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The grant form posts a checkbox array. Names are validated against the
 * permissions table here and filtered against the grantable catalog in
 * PermissionService, so neither a stale checkbox nor a crafted POST can hand
 * out something the admin never offered.
 */
class SyncPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /** @return array<int, string> */
    public function permissionNames(): array
    {
        return array_values(array_filter((array) $this->input('permissions', [])));
    }
}
