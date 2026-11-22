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

    /**
     * Per-rep presence for the filtered range: `mr_id => [...]`.
     *
     * Minutes are summed in PHP rather than SQL on purpose. The two engines
     * spell the difference between timestamps differently (TIMESTAMPDIFF vs
     * strftime), and one query returning a few hundred thin rows costs less
     * than a portability wrapper. The shape is per-rep, so the Reports module
     * never has to touch this table itself.
     *
     * @return array<int, array{days_present: int, days_completed: int, days_open: int, minutes: int}>
     */
    public function perMrSummary(array $filters): array
    {
        $rows = $this->applyFilters($this->query(), $filters)
            ->select(['mr_id', 'check_in_at', 'check_out_at'])
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $id = (int) $row->mr_id;

            $out[$id] ??= ['days_present' => 0, 'days_completed' => 0, 'days_open' => 0, 'minutes' => 0];

            if (! $row->isCheckedIn()) {
                continue;
            }

            $out[$id]['days_present']++;

            if ($row->isCheckedOut()) {
                $out[$id]['days_completed']++;
                $out[$id]['minutes'] += (int) $row->workedMinutes();
            } else {
                $out[$id]['days_open']++;
            }
        }

        return $out;
    }

    /**
     * The attendance register: `mr_id => ['YYYY-MM-DD' => 'P'|'O'|'A']`.
     *
     * Only days that have a row appear. A day with no row is absence, which
     * the caller fills in — it knows the range it asked for, and storing a
     * row per absent day would mean writing rows nobody created.
     *
     * @return array<int, array<string, string>>
     */
    public function registerGrid(array $filters): array
    {
        $rows = $this->applyFilters($this->query(), $filters)
            ->select(['mr_id', 'date', 'check_in_at', 'check_out_at'])
            ->orderBy('date')
            ->get();

        $grid = [];

        foreach ($rows as $row) {
            $grid[(int) $row->mr_id][$row->date->toDateString()] = match (true) {
                $row->isCheckedOut() => 'P',
                $row->isCheckedIn() => 'O',
                default => 'A',
            };
        }

        return $grid;
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
