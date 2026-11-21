<?php

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The user shape both Flutter apps decode. Keep it stable — adding a field is
 * safe, renaming one is a breaking change for shipped app builds.
 *
 * @property User $resource
 */
class AuthUserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'mobile' => $this->resource->mobile,
            'role' => $this->resource->role?->value,
            'role_label' => $this->resource->roleLabel(),
            'is_active' => $this->resource->is_active,
            // Phase 2 replaces this with the region (and the telecaller's list).
            'region_id' => $this->resource->region_id,
            'regions' => [],
            'permissions' => $this->resource->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
