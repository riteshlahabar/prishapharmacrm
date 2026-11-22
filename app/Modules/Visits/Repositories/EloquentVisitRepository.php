<?php

namespace App\Modules\Visits\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Visits\Models\Visit;
use App\Modules\Visits\Repositories\Contracts\VisitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Every `visits` query in the application lives here.
 */
class EloquentVisitRepository extends EloquentRepository implements VisitRepositoryInterface
{
    protected function model(): Model
    {
        return new Visit;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach (['mr_id' => 'mr_id', 'person_id' => 'person_id', 'purpose' => 'purpose'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }

        // Region lives on the person, not the visit.
        if (! empty($filters['region_id'])) {
            $query->whereHas('person', fn (Builder $q) => $q->where('region_id', $filters['region_id']));
        }

        if (array_key_exists('region_scope', $filters)) {
            $scope = (array) $filters['region_scope'];
            $scope === []
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('person', fn (Builder $q) => $q->whereIn('region_id', $scope));
        }

        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $q) use ($like) {
                $q->whereHas('person', fn (Builder $p) => $p->where('name', 'like', $like))
                    ->orWhereHas('mr', fn (Builder $m) => $m->where('name', 'like', $like))
                    ->orWhere('notes', 'like', $like);
            });
        }

        if (! empty($filters['without_location'])) {
            $query->whereNull('latitude');
        }

        return $query;
    }

    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->with(['mr', 'person.region'])
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Visits per day, as `YYYY-MM-DD => count`. Only days that have rows come
     * back; the Dashboard zero-fills the range, which is a presentation
     * decision and not this layer's business.
     *
     * `DATE()` with an explicit alias, because `visit_date` is cast to a date
     * and plucking the column itself would key the array by a Carbon-rendered
     * value rather than the plain day the chart needs.
     */
    public function trend(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->selectRaw('DATE(visit_date) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->all();
    }

    /**
     * Busiest reps first, as `mr name => count`. Grouped by name, not id: the
     * result is keyed by name, so grouping by id would let two reps sharing a
     * name overwrite each other and quietly lose one from the chart.
     */
    public function countsByMr(array $filters, int $limit = 10): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->join('users', 'users.id', '=', 'visits.mr_id')
            ->selectRaw('users.name as mr, count(*) as total')
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'mr')
            ->all();
    }

    /**
     * Per-rep visit figures for the range: `mr_id => [...]`.
     *
     * `active_days` counts distinct dates, not rows, so "visits per active
     * day" divides by days the rep actually worked rather than by the length
     * of the range — otherwise a rep on leave looks lazy rather than absent.
     *
     * @return array<int, array{visits: int, persons: int, geotagged: int, active_days: int}>
     */
    public function perMrSummary(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->selectRaw('mr_id')
            ->selectRaw('count(*) as visits')
            ->selectRaw('count(distinct person_id) as persons')
            ->selectRaw('count(distinct case when latitude is not null then id end) as geotagged')
            ->selectRaw('count(distinct visit_date) as active_days')
            ->groupBy('mr_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->mr_id => [
                'visits' => (int) $row->visits,
                'persons' => (int) $row->persons,
                'geotagged' => (int) $row->geotagged,
                'active_days' => (int) $row->active_days,
            ]])
            ->all();
    }

    /**
     * Visits per region, `region_id => count`, read through the person the
     * visit was on — a visit has no region of its own.
     *
     * @return array<int, int>
     */
    public function countsByRegion(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->join('persons', 'persons.id', '=', 'visits.person_id')
            ->whereNotNull('persons.region_id')
            ->selectRaw('persons.region_id as region_id, count(*) as total')
            ->groupBy('persons.region_id')
            ->pluck('total', 'region_id')
            ->map(intval(...))
            ->all();
    }

    /**
     * The newest visit per person, keyed by `person_id`. Mirrors the Calls
     * module's `lastCallPerPerson`, and is what the coverage report needs to
     * tell "never visited" from "visited months ago".
     *
     * @param  array<int, int>  $personIds
     * @return Collection<int, Visit>
     */
    public function lastVisitPerPerson(array $personIds): Collection
    {
        if ($personIds === []) {
            return new Collection;
        }

        // The newest id per person, then the rows for those ids: two small
        // queries beat a correlated subquery per row.
        $latestIds = $this->query()
            ->whereIn('person_id', $personIds)
            ->groupBy('person_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id')
            ->all();

        return $this->query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('person_id');
    }

    public function summary(array $filters): array
    {
        $base = fn () => $this->applyFilters($this->query(), $filters);

        return [
            'total' => $base()->count(),
            'persons' => $base()->distinct()->count('person_id'),
            'mrs' => $base()->distinct()->count('mr_id'),
            'geotagged' => $base()->whereNotNull('latitude')->count(),
            'by_purpose' => $base()
                ->groupBy('purpose')
                ->selectRaw('purpose, count(*) as total')
                ->pluck('total', 'purpose')
                ->all(),
        ];
    }
}
