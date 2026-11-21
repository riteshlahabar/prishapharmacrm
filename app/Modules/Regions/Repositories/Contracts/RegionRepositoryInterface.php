<?php

namespace App\Modules\Regions\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Persistence for regions. Other modules reach regions through
 * RegionServiceInterface, never through this directly (docs/conventions.md).
 */
interface RegionRepositoryInterface extends RepositoryInterface
{
    /** Active regions, name-ordered — the options behind every region picker. */
    public function activeOptions(): Collection;

    /** Counts of MRs, telecallers and persons, keyed by region id. */
    public function usageCounts(array $regionIds): array;
}
