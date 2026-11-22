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
