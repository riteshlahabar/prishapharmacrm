<?php

namespace App\Modules\Regions\Services;

use App\Modules\Regions\Models\Region;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Support\Collection;

class RegionAssignmentService implements RegionAssignmentServiceInterface
{
    public function assign(User $user, array $regionIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $regionIds))));

        match ($user->role) {
            // An MR travels one territory: the first id wins, the rest are noise.
            UserRole::Mr => $this->assignSingle($user, $ids[0] ?? null),
            UserRole::Telecaller => $user->regions()->sync($ids),
            default => null,
        };
    }

    public function idsFor(User $user): array
    {
        return match ($user->role) {
            UserRole::Mr => $user->region_id ? [(int) $user->region_id] : [],
            UserRole::Telecaller => $user->regions()->pluck('regions.id')->map(intval(...))->all(),
            default => [],
        };
    }

    public function regionsFor(User $user): Collection
    {
        return match ($user->role) {
            UserRole::Mr => Region::query()->whereKey($user->region_id)->get(),
            UserRole::Telecaller => $user->regions()->orderBy('name')->get(),
            default => new Collection,
        };
    }

    private function assignSingle(User $user, ?int $regionId): void
    {
        if ($user->region_id !== $regionId) {
            $user->forceFill(['region_id' => $regionId])->save();
        }
    }
}
