<?php

namespace App\Modules\Regions\Services;

use App\Modules\Regions\Exceptions\RegionInUse;
use App\Modules\Regions\Models\Region;
use App\Modules\Regions\Repositories\Contracts\RegionRepositoryInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RegionService implements RegionServiceInterface
{
    public function __construct(private readonly RegionRepositoryInterface $regions) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->regions->paginate($perPage, $filters);
    }

    public function find(int|string $id): Region
    {
        /** @var Region */
        return $this->regions->findOrFail($id);
    }

    public function create(array $data): Region
    {
        /** @var Region */
        return $this->regions->create($this->attributes($data));
    }

    public function update(int|string $id, array $data): Region
    {
        /** @var Region */
        return $this->regions->update($id, $this->attributes($data));
    }

    /**
     * Refused while anyone still points at the region — deactivating is the
     * non-destructive way to retire one.
     */
    public function delete(int|string $id): bool
    {
        $region = $this->find($id);
        $counts = $this->usageCounts([$region->id])[$region->id] ?? [];

        if (array_sum($counts) > 0) {
            throw RegionInUse::make($region->name, $counts);
        }

        return $this->regions->delete($id);
    }

    public function toggleActive(int|string $id): bool
    {
        $region = $this->find($id);
        $region->is_active = ! $region->is_active;
        $region->save();

        return $region->is_active;
    }

    public function activeOptions(): Collection
    {
        return $this->regions->activeOptions();
    }

    public function usageCounts(array $regionIds): array
    {
        return $this->regions->usageCounts($regionIds);
    }

    private function attributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'code' => strtoupper((string) $data['code']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
