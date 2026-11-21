<?php

namespace App\Modules\Regions\Services\Contracts;

use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Illuminate\Support\Collection;

/**
 * Who covers which territory. An MR has exactly one region
 * (`users.region_id`); a telecaller has many (`telecaller_region`). Keeping
 * both behind one contract means the Users module never has to know which
 * shape applies to the role it is saving.
 */
interface RegionAssignmentServiceInterface
{
    /**
     * Apply whatever region input the form supplied, in the shape that role
     * uses. Ignored for an admin.
     *
     * @param  array<int, int|string>  $regionIds
     */
    public function assign(User $user, array $regionIds): void;

    /** Region ids currently covered by this user, whichever shape they use. */
    public function idsFor(User $user): array;

    /** @return Collection<int, Region> */
    public function regionsFor(User $user): Collection;
}
