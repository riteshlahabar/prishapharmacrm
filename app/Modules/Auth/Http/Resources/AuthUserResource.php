<?php

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
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
            // An MR's single territory; null for telecallers and admins, who
            // use `regions` below. Kept for the shipped app builds that read it.
            'region_id' => $this->resource->region_id,
            // Every region this account covers, whichever shape the role
            // uses — one row for an MR, many for a telecaller. The telecaller
            // app needs this to label its region-scoped person list, and it
            // stood empty from Phase 2 until Phase 6.
            'regions' => $this->regions(),
            'permissions' => $this->resource->getAllPermissions()->pluck('name')->values(),
        ];
    }

    /**
     * Through the Regions service contract, never the pivot directly: which
     * table holds an assignment is that module's business (docs/conventions.md).
     * Resolved from the container because a JsonResource cannot take
     * constructor injection, and both `login` and `me` must always carry this.
     *
     * @return array<int, array<string, mixed>>
     */
    private function regions(): array
    {
        return app(RegionAssignmentServiceInterface::class)
            ->regionsFor($this->resource)
            ->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'code' => $region->code,
            ])
            ->values()
            ->all();
    }
}
