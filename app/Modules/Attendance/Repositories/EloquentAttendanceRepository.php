<?php

namespace App\Modules\Attendance\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Attendance\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every `attendances` query in the application lives here.
 */
class EloquentAttendanceRepository extends EloquentRepository implements AttendanceRepositoryInterface
{
    protected function model(): Model
    {
        return new Attendance;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['mr_id'])) {
            $query->where('mr_id', $filters['mr_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        // An MR belongs to one region, so this filters through the owner.
        if (! empty($filters['region_id'])) {
            $query->whereHas('mr', fn (Builder $q) => $q->where('region_id', $filters['region_id']));
        }

        // The territory clamp a scoped viewer gets. An empty list means "no
        // regions yet", which must return nothing rather than everything —
        // the same contract as the Visits and Persons repositories.
        if (array_key_exists('region_scope', $filters)) {
            $scope = (array) $filters['region_scope'];
            $scope === []
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('mr', fn (Builder $q) => $q->whereIn('region_id', $scope));
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            match ($status) {
                'on_duty' => $query->whereNotNull('check_in_at')->whereNull('check_out_at'),
                'completed' => $query->whereNotNull('check_out_at'),
                default => null,
            };
        }

        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $like = "%{$search}%";
            $query->whereHas('mr', fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        return $query;
    }

    public function forDate(int $mrId, string $date): ?Attendance
    {
        return $this->query()
            ->where('mr_id', $mrId)
            ->whereDate('date', $date)
            ->first();
    }

    public function firstOrCreateForDate(int $mrId, string $date): Attendance
    {
        return Attendance::firstOrCreate(['mr_id' => $mrId, 'date' => $date]);
    }

    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->with(['mr.region'])
            ->orderByDesc('date')
            ->orderBy('mr_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Reps present per day, as `YYYY-MM-DD => count`. Counts distinct reps
     * with a check-in, not rows: presence is the metric, and a day with no
     * check-in is absence however the row got there.
     */
    public function trend(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->whereNotNull('check_in_at')
            ->selectRaw('DATE(date) as day, count(distinct mr_id) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->all();
    }

    public function summary(array $filters): array
    {
        // A fresh builder per count: reusing one would stack the constraints.
        $base = fn () => $this->applyFilters($this->query(), $filters);

        return [
            'rows' => $base()->count(),
            'on_duty' => $base()->whereNotNull('check_in_at')->whereNull('check_out_at')->count(),
            'completed' => $base()->whereNotNull('check_out_at')->count(),
        ];
    }
}
