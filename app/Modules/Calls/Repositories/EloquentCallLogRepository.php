<?php

namespace App\Modules\Calls\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Calls\Repositories\Contracts\CallLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Every `call_logs` query in the application lives here.
 */
class EloquentCallLogRepository extends EloquentRepository implements CallLogRepositoryInterface
{
    protected function model(): Model
    {
        return new CallLog;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach (['telecaller_id' => 'telecaller_id', 'person_id' => 'person_id', 'status' => 'status'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('called_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('called_at', '<=', $filters['date_to']);
        }

        // Region lives on the person, not the call.
        if (! empty($filters['region_id'])) {
            $query->whereHas('person', fn (Builder $q) => $q->where('region_id', $filters['region_id']));
        }

        if (array_key_exists('region_scope', $filters)) {
            $scope = (array) $filters['region_scope'];
            $scope === []
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('person', fn (Builder $q) => $q->whereIn('region_id', $scope));
        }

        // "Only follow-ups" and "only the ones already due" are separate
        // questions: the panel lists promises, the app's queue lists work.
        if (! empty($filters['follow_up'])) {
            $query->whereNotNull('follow_up_at')->whereNull('follow_up_done_at');

            if (! empty($filters['follow_up_due'])) {
                $query->where('follow_up_at', '<=', now());
            }
        }

        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $q) use ($like) {
                $q->whereHas('person', fn (Builder $p) => $p->where('name', 'like', $like)->orWhere('mobile', 'like', $like))
                    ->orWhereHas('telecaller', fn (Builder $t) => $t->where('name', 'like', $like))
                    ->orWhere('notes', 'like', $like);
            });
        }

        return $query;
    }

    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->with(['telecaller', 'person.region'])
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Calls per day, as `YYYY-MM-DD => count`, days with rows only — the
     * Dashboard zero-fills the range. `DATE()` because `called_at` is a
     * timestamp and the chart plots days.
     */
    public function trend(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->selectRaw('DATE(called_at) as day, count(*) as total')
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
            'total' => $base()->count(),
            'persons' => $base()->distinct()->count('person_id'),
            'telecallers' => $base()->distinct()->count('telecaller_id'),
            'converted' => $base()->where('status', 'converted')->count(),
            'follow_ups_open' => $base()->whereNotNull('follow_up_at')->whereNull('follow_up_done_at')->count(),
            'by_status' => $base()
                ->groupBy('status')
                ->selectRaw('status, count(*) as total')
                ->pluck('total', 'status')
                ->all(),
        ];
    }

    /**
     * Per-telecaller call figures for the range: `telecaller_id => [...]`.
     *
     * `converted` is counted here rather than derived from `by_status` later,
     * so the rate on the report and the slice on the dashboard cannot drift.
     *
     * @return array<int, array{calls: int, persons: int, converted: int, active_days: int}>
     */
    public function perTelecallerSummary(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->selectRaw('telecaller_id')
            ->selectRaw('count(*) as calls')
            ->selectRaw('count(distinct person_id) as persons')
            ->selectRaw("count(case when status = 'converted' then 1 end) as converted")
            ->selectRaw('count(distinct date(called_at)) as active_days')
            ->groupBy('telecaller_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->telecaller_id => [
                'calls' => (int) $row->calls,
                'persons' => (int) $row->persons,
                'converted' => (int) $row->converted,
                'active_days' => (int) $row->active_days,
            ]])
            ->all();
    }

    /**
     * Per-telecaller status mix: `telecaller_id => [status => count]`.
     *
     * @return array<int, array<string, int>>
     */
    public function statusMixByTelecaller(array $filters): array
    {
        $out = [];

        $rows = $this->applyFilters($this->query(), $filters)
            ->selectRaw('telecaller_id, status, count(*) as total')
            ->groupBy('telecaller_id', 'status')
            ->get();

        foreach ($rows as $row) {
            $status = $row->status instanceof CallStatus ? $row->status->value : (string) $row->status;
            $out[(int) $row->telecaller_id][$status] = (int) $row->total;
        }

        return $out;
    }

    /**
     * Did the promised call-backs actually happen? Per telecaller:
     * `telecaller_id => {promised, honoured, on_time, overdue_open}`.
     *
     * `on_time` compares `follow_up_done_at` to the date promised, which is
     * the only figure here that says anything about reliability — `honoured`
     * alone counts a call-back made three weeks late as a success.
     *
     * @return array<int, array{promised: int, honoured: int, on_time: int, overdue_open: int}>
     */
    public function followUpCompliance(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->whereNotNull('follow_up_at')
            ->selectRaw('telecaller_id')
            ->selectRaw('count(*) as promised')
            ->selectRaw('count(case when follow_up_done_at is not null then 1 end) as honoured')
            ->selectRaw('count(case when follow_up_done_at is not null and follow_up_done_at <= follow_up_at then 1 end) as on_time')
            ->selectRaw('count(case when follow_up_done_at is null and follow_up_at < ? then 1 end) as overdue_open', [now()])
            ->groupBy('telecaller_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->telecaller_id => [
                'promised' => (int) $row->promised,
                'honoured' => (int) $row->honoured,
                'on_time' => (int) $row->on_time,
                'overdue_open' => (int) $row->overdue_open,
            ]])
            ->all();
    }

    /**
     * Calls per region, `region_id => count`, read through the person called.
     *
     * @return array<int, int>
     */
    public function countsByRegion(array $filters): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->join('persons', 'persons.id', '=', 'call_logs.person_id')
            ->whereNotNull('persons.region_id')
            ->selectRaw('persons.region_id as region_id, count(*) as total')
            ->groupBy('persons.region_id')
            ->pluck('total', 'region_id')
            ->map(intval(...))
            ->all();
    }

    public function lastCallPerPerson(array $personIds, ?int $telecallerId = null): Collection
    {
        if ($personIds === []) {
            return new Collection;
        }

        /**
         * The newest id per person, then the rows for those ids. Two small
         * queries beat a correlated subquery per row, and `id` breaks the tie
         * when two calls share a `called_at` to the second.
         */
        $latestIds = $this->query()
            ->whereIn('person_id', $personIds)
            ->when($telecallerId !== null, fn (Builder $q) => $q->where('telecaller_id', $telecallerId))
            ->groupBy('person_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id')
            ->all();

        return $this->query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('person_id');
    }

    public function followUps(int $telecallerId, bool $dueOnly, int $perPage): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), [
            'telecaller_id' => $telecallerId,
            'follow_up' => true,
            'follow_up_due' => $dueOnly,
        ])
            ->with(['person.region'])
            ->orderBy('follow_up_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function closeOpenFollowUps(int $telecallerId, int $personId, ?int $exceptId = null): int
    {
        return $this->query()
            ->where('telecaller_id', $telecallerId)
            ->where('person_id', $personId)
            ->whereNotNull('follow_up_at')
            ->whereNull('follow_up_done_at')
            ->when($exceptId !== null, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->update(['follow_up_done_at' => now()]);
    }
}
