<?php

namespace App\Modules\Regions\Services\Contracts;

use App\Modules\Regions\Exceptions\RegionInUse;
use App\Modules\Regions\Models\Region;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Region master data. Other modules depend on this contract — never on the
 * Region model or its repository (docs/conventions.md).
 */
interface RegionServiceInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function find(int|string $id): Region;

    public function create(array $data): Region;

    public function update(int|string $id, array $data): Region;

    /** @throws RegionInUse */
    public function delete(int|string $id): bool;

    public function toggleActive(int|string $id): bool;

    /** Options for every region picker in the panel. */
    public function activeOptions(): Collection;

    /** @return array<int, array{mrs:int, telecallers:int, persons:int}> */
    public function usageCounts(array $regionIds): array;
}
