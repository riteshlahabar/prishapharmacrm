<?php

namespace App\Modules\Regions\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Regions\Models\Region;
use App\Modules\Regions\Repositories\Contracts\RegionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every `regions` query in the application lives here.
 */
class EloquentRegionRepository extends EloquentRepository implements RegionRepositoryInterface
{
    protected function model(): Model
    {
        return new Region;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $like = "%{$search}%";
            $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like));
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        return $query;
    }

    public function activeOptions(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * One grouped query per relation rather than a count per row, so a long
     * region list does not turn into N+1.
     */
    public function usageCounts(array $regionIds): array
    {
        if ($regionIds === []) {
            return [];
        }

        $counts = array_fill_keys($regionIds, ['mrs' => 0, 'telecallers' => 0, 'persons' => 0]);

        $sources = [
            'mrs' => DB::table('users')->where('role', 'mr')->whereIn('region_id', $regionIds)
                ->groupBy('region_id')->selectRaw('region_id, count(*) as total'),
            'telecallers' => DB::table('telecaller_region')->whereIn('region_id', $regionIds)
                ->groupBy('region_id')->selectRaw('region_id, count(*) as total'),
        ];

        if (DB::getSchemaBuilder()->hasTable('persons')) {
            $sources['persons'] = DB::table('persons')->whereIn('region_id', $regionIds)
                ->groupBy('region_id')->selectRaw('region_id, count(*) as total');
        }

        foreach ($sources as $key => $source) {
            foreach ($source->get() as $row) {
                $counts[$row->region_id][$key] = (int) $row->total;
            }
        }

        return $counts;
    }
}
