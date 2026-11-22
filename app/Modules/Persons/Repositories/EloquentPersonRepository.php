<?php

namespace App\Modules\Persons\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Persons\Models\Person;
use App\Modules\Persons\Repositories\Contracts\PersonRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every `persons` query in the application lives here.
 */
class EloquentPersonRepository extends EloquentRepository implements PersonRepositoryInterface
{
    protected function model(): Model
    {
        return new Person;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('city', 'like', $like);
            });
        }

        foreach (['type' => 'type', 'region_id' => 'region_id',
            'assigned_mr_id' => 'assigned_mr_id', 'assigned_telecaller_id' => 'assigned_telecaller_id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        // "unassigned" is a question the panel asks often enough to be a filter.
        if (! empty($filters['unassigned'])) {
            $query->where(fn (Builder $q) => $q->whereNull('assigned_mr_id')->orWhereNull('assigned_telecaller_id'));
        }

        /**
         * Region scoping for the telecaller side: pass the regions a user
         * covers and they see nothing outside them. An empty array means "no
         * regions assigned", which must show nothing rather than everything.
         */
        if (array_key_exists('region_scope', $filters)) {
            $scope = (array) $filters['region_scope'];
            $scope === [] ? $query->whereRaw('1 = 0') : $query->whereIn('region_id', $scope);
        }

        return $query;
    }

    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->with(['region', 'assignedMr', 'assignedTelecaller'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** How many rows match, without pulling a page of them. */
    public function countMatching(array $filters): int
    {
        return $this->applyFilters($this->query(), $filters)->count();
    }

    /**
     * Active persons assigned to each rep: `assigned_mr_id => count`.
     *
     * This is the denominator of the MR report's coverage figure — "of the
     * contacts that are this rep's responsibility, how many did they get to".
     * Unassigned persons are left out: they are nobody's coverage gap yet.
     *
     * @return array<int, int>
     */
    public function activeCountsByMr(array $filters = []): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->where('is_active', true)
            ->whereNotNull('assigned_mr_id')
            ->selectRaw('assigned_mr_id, count(*) as total')
            ->groupBy('assigned_mr_id')
            ->pluck('total', 'assigned_mr_id')
            ->map(intval(...))
            ->all();
    }

    public function countsByRegion(array $regionIds): array
    {
        if ($regionIds === []) {
            return [];
        }

        return $this->query()
            ->whereIn('region_id', $regionIds)
            ->groupBy('region_id')
            ->selectRaw('region_id, count(*) as total')
            ->pluck('total', 'region_id')
            ->map(intval(...))
            ->all();
    }
}
