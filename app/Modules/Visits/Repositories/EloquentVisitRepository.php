<?php

namespace App\Modules\Visits\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Visits\Models\Visit;
use App\Modules\Visits\Repositories\Contracts\VisitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
