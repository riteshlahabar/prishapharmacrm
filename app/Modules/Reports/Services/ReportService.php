<?php

namespace App\Modules\Reports\Services;

use App\Modules\Attendance\Services\Contracts\AttendanceServiceInterface;
use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;
use App\Modules\Messages\Services\Contracts\MessageServiceInterface;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Reports\Services\Contracts\ReportServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Composes the management reports out of other modules' figures.
 *
 * This module owns no table and runs no query. Every number arrives through a
 * sibling module's `…ForUser` contract method, so the territory clamp stays
 * with the module that owns the data and this class cannot widen it
 * (docs/conventions.md, and the Phase 7 dashboard precedent).
 */
class ReportService implements ReportServiceInterface
{
    /** The period a field manager reviews, when they do not say. */
    private const DEFAULT_DAYS = 30;

    /** "Not visited in this many days" counts as neglected, by default. */
    public const STALE_DAYS = 30;

    public function __construct(
        private readonly AttendanceServiceInterface $attendance,
        private readonly VisitServiceInterface $visits,
        private readonly CallLogServiceInterface $calls,
        private readonly MessageServiceInterface $messages,
        private readonly PersonServiceInterface $persons,
        private readonly RegionServiceInterface $regions,
        private readonly RegionAssignmentServiceInterface $assignment,
        private readonly UserServiceInterface $users,
    ) {}

    public function period(?string $from, ?string $to): array
    {
        $end = $this->parse($to) ?? Carbon::today();
        $start = $this->parse($from) ?? $end->copy()->subDays(self::DEFAULT_DAYS - 1);

        // A backwards range is a typo, not an error worth a 422 on a report.
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'days' => (int) $start->diffInDays($end) + 1,
        ];
    }

    public function mrPerformance(User $viewer, array $filters): array
    {
        $range = $this->range($filters);
        $reps = $this->repsVisibleTo($viewer);

        $presence = $this->attendance->perMrSummaryForUser($viewer, $range);
        $visits = $this->visits->perMrSummaryForUser($viewer, $range);
        // Assignment is a standing fact, not a thing that happened in the
        // range, so the coverage denominator is deliberately unfiltered by date.
        $assigned = $this->persons->activeCountsByMrForUser($viewer);

        $rows = [];

        foreach ($reps as $rep) {
            $id = (int) $rep->id;
            $p = $presence[$id] ?? ['days_present' => 0, 'days_completed' => 0, 'days_open' => 0, 'minutes' => 0];
            $v = $visits[$id] ?? ['visits' => 0, 'persons' => 0, 'geotagged' => 0, 'active_days' => 0];
            $pool = (int) ($assigned[$id] ?? 0);

            $rows[] = [
                'id' => $id,
                'name' => $rep->name,
                'region' => $rep->region?->name ?? '—',
                'days_present' => $p['days_present'],
                'days_open' => $p['days_open'],
                'hours' => $this->hours($p['minutes']),
                'avg_hours' => $p['days_completed'] > 0
                    ? round($p['minutes'] / $p['days_completed'] / 60, 1)
                    : null,
                'visits' => $v['visits'],
                'persons_seen' => $v['persons'],
                'active_days' => $v['active_days'],
                // Per day actually worked, not per day in the range: a rep on
                // leave should not read as unproductive.
                'visits_per_day' => $v['active_days'] > 0
                    ? round($v['visits'] / $v['active_days'], 1)
                    : null,
                'assigned' => $pool,
                'coverage' => $this->percent($v['persons'], $pool),
                'geotagged' => $this->percent($v['geotagged'], $v['visits']),
            ];
        }

        return [
            'rows' => $this->sortRows($rows, $filters['sort'] ?? 'visits'),
            'totals' => [
                'reps' => count($rows),
                'days_present' => array_sum(array_column($rows, 'days_present')),
                'visits' => array_sum(array_column($rows, 'visits')),
                'persons_seen' => array_sum(array_column($rows, 'persons_seen')),
            ],
        ];
    }

    public function telecallerPerformance(User $viewer, array $filters): array
    {
        $range = $this->range($filters);
        $callers = $this->telecallersVisibleTo($viewer);

        $summary = $this->calls->perTelecallerSummaryForUser($viewer, $range);
        $mix = $this->calls->statusMixForUser($viewer, $range);
        $compliance = $this->calls->followUpComplianceForUser($viewer, $range);
        $sent = $this->messages->sentCountsForUser($viewer, $range);

        $rows = [];

        foreach ($callers as $caller) {
            $id = (int) $caller->id;
            $c = $summary[$id] ?? ['calls' => 0, 'persons' => 0, 'converted' => 0, 'active_days' => 0];
            $f = $compliance[$id] ?? ['promised' => 0, 'honoured' => 0, 'on_time' => 0, 'overdue_open' => 0];
            $statuses = $mix[$id] ?? [];

            $rows[] = [
                'id' => $id,
                'name' => $caller->name,
                'regions' => $caller->regions->pluck('code')->implode(', ') ?: '—',
                'calls' => $c['calls'],
                'persons_called' => $c['persons'],
                'active_days' => $c['active_days'],
                'calls_per_day' => $c['active_days'] > 0 ? round($c['calls'] / $c['active_days'], 1) : null,
                'converted' => $c['converted'],
                'conversion' => $this->percent($c['converted'], $c['calls']),
                'interested' => (int) ($statuses[CallStatus::Interested->value] ?? 0),
                'not_reachable' => (int) ($statuses[CallStatus::NotReachable->value] ?? 0),
                'callbacks' => $f['promised'],
                'overdue' => $f['overdue_open'],
                'messages_sent' => (int) ($sent[$id] ?? 0),
            ];
        }

        return [
            'rows' => $this->sortRows($rows, $filters['sort'] ?? 'calls'),
            'totals' => [
                'telecallers' => count($rows),
                'calls' => array_sum(array_column($rows, 'calls')),
                'converted' => array_sum(array_column($rows, 'converted')),
                'overdue' => array_sum(array_column($rows, 'overdue')),
            ],
        ];
    }

    public function coverage(User $viewer, array $filters): array
    {
        $stale = max(1, (int) ($filters['stale_days'] ?? self::STALE_DAYS));
        $cutoff = Carbon::today()->subDays($stale);

        // The person list is clamped by the Persons module; a generous page
        // keeps the report usable without loading the whole book.
        $page = $this->persons->paginateForUser($viewer, [
            'status' => 'active',
            'search' => $filters['search'] ?? null,
            'region_id' => $filters['region_id'] ?? null,
            'type' => $filters['type'] ?? null,
        ], 100);

        $ids = collect($page->items())->pluck('id')->map(intval(...))->all();
        $lastVisits = $this->visits->lastVisitsFor($ids);
        $lastCalls = $this->calls->lastCallsFor($viewer, $ids);

        $rows = [];

        foreach ($page->items() as $person) {
            $visit = $lastVisits->get($person->id);
            $call = $lastCalls->get($person->id);

            $visitedAt = $visit?->visit_date;
            $calledAt = $call?->called_at;

            $visitStale = $visitedAt === null || $visitedAt->lessThan($cutoff);
            $callStale = $calledAt === null || $calledAt->lessThan($cutoff);

            // Touched by somebody recently: not a gap, so leave it out.
            if (! $visitStale && ! $callStale) {
                continue;
            }

            $rows[] = [
                'id' => $person->id,
                'name' => $person->name,
                'type' => $person->typeLabel(),
                'region' => $person->region?->name ?? '—',
                'mobile' => $person->mobile ?? '—',
                'assigned_mr' => $person->assignedMr?->name ?? 'unassigned',
                'last_visit' => $visitedAt?->format('d M Y') ?? 'never',
                'visit_age' => $visitedAt !== null ? (int) $visitedAt->diffInDays(Carbon::today()) : null,
                'last_call' => $calledAt?->format('d M Y') ?? 'never',
                'call_age' => $calledAt !== null ? (int) $calledAt->diffInDays(Carbon::today()) : null,
                'never_touched' => $visitedAt === null && $calledAt === null,
            ];
        }

        // Worst first: never touched, then the longest neglected.
        usort($rows, function (array $a, array $b) {
            if ($a['never_touched'] !== $b['never_touched']) {
                return $a['never_touched'] ? -1 : 1;
            }

            return ($b['visit_age'] ?? PHP_INT_MAX) <=> ($a['visit_age'] ?? PHP_INT_MAX);
        });

        return [
            'rows' => $rows,
            'paginator' => $page,
            'stale_days' => $stale,
            'totals' => [
                'gaps' => count($rows),
                'never' => count(array_filter(array_column($rows, 'never_touched'))),
                'scanned' => $page->count(),
                'total' => $page->total(),
            ],
        ];
    }

    public function regionRollup(User $viewer, array $filters): array
    {
        $range = $this->range($filters);
        $regions = $viewer->isAdmin()
            ? $this->regions->activeOptions()
            : $this->assignment->regionsFor($viewer);

        $ids = $regions->pluck('id')->map(intval(...))->all();
        $usage = $this->regions->usageCounts($ids);
        $visits = $this->visits->countsByRegionForUser($viewer, $range);
        $calls = $this->calls->countsByRegionForUser($viewer, $range);

        $rows = [];

        foreach ($regions as $region) {
            $id = (int) $region->id;
            $use = $usage[$id] ?? ['mrs' => 0, 'telecallers' => 0, 'persons' => 0];

            $rows[] = [
                'id' => $id,
                'name' => $region->name,
                'code' => $region->code,
                'mrs' => (int) ($use['mrs'] ?? 0),
                'telecallers' => (int) ($use['telecallers'] ?? 0),
                'persons' => (int) ($use['persons'] ?? 0),
                'visits' => (int) ($visits[$id] ?? 0),
                'calls' => (int) ($calls[$id] ?? 0),
                // Activity per contact, so a big region is not flattered by
                // its size alone.
                'touches_per_person' => ($use['persons'] ?? 0) > 0
                    ? round(((int) ($visits[$id] ?? 0) + (int) ($calls[$id] ?? 0)) / $use['persons'], 1)
                    : null,
            ];
        }

        return [
            'rows' => $this->sortRows($rows, $filters['sort'] ?? 'visits'),
            'totals' => [
                'regions' => count($rows),
                'persons' => array_sum(array_column($rows, 'persons')),
                'visits' => array_sum(array_column($rows, 'visits')),
                'calls' => array_sum(array_column($rows, 'calls')),
            ],
        ];
    }

    public function attendanceRegister(User $viewer, array $filters): array
    {
        $range = $this->range($filters);
        $reps = $this->repsVisibleTo($viewer);
        $grid = $this->attendance->registerForUser($viewer, $range);

        $days = [];
        $cursor = Carbon::parse($range['date_from']);
        $end = Carbon::parse($range['date_to']);

        // A register is a fixed grid: every day in the range gets a column,
        // including the ones nobody worked.
        while ($cursor->lessThanOrEqualTo($end)) {
            $days[] = ['date' => $cursor->toDateString(), 'label' => $cursor->format('j'), 'dow' => $cursor->format('D')];
            $cursor->addDay();
        }

        $rows = [];

        foreach ($reps as $rep) {
            $marks = $grid[(int) $rep->id] ?? [];
            $cells = [];
            $present = 0;

            foreach ($days as $day) {
                // No row means absent. The MR app writes a row when someone
                // checks in, so a missing day is genuinely a missing day.
                $mark = $marks[$day['date']] ?? 'A';
                $cells[] = $mark;

                if ($mark !== 'A') {
                    $present++;
                }
            }

            $rows[] = [
                'id' => $rep->id,
                'name' => $rep->name,
                'region' => $rep->region?->name ?? '—',
                'cells' => $cells,
                'present' => $present,
                'absent' => count($days) - $present,
            ];
        }

        return [
            'rows' => $rows,
            'days' => $days,
            'totals' => ['reps' => count($rows), 'days' => count($days)],
        ];
    }

    public function followUpCompliance(User $viewer, array $filters): array
    {
        $range = $this->range($filters);
        $callers = $this->telecallersVisibleTo($viewer);
        $compliance = $this->calls->followUpComplianceForUser($viewer, $range);

        $rows = [];

        foreach ($callers as $caller) {
            $f = $compliance[(int) $caller->id]
                ?? ['promised' => 0, 'honoured' => 0, 'on_time' => 0, 'overdue_open' => 0];

            // Nothing promised, nothing to judge.
            if ($f['promised'] === 0) {
                continue;
            }

            $rows[] = [
                'id' => $caller->id,
                'name' => $caller->name,
                'promised' => $f['promised'],
                'honoured' => $f['honoured'],
                'on_time' => $f['on_time'],
                'late' => $f['honoured'] - $f['on_time'],
                'open' => $f['promised'] - $f['honoured'],
                'overdue' => $f['overdue_open'],
                'kept' => $this->percent($f['honoured'], $f['promised']),
                // The figure that matters: kept *by the date promised*.
                'punctual' => $this->percent($f['on_time'], $f['promised']),
            ];
        }

        return [
            'rows' => $this->sortRows($rows, $filters['sort'] ?? 'overdue'),
            'totals' => [
                'promised' => array_sum(array_column($rows, 'promised')),
                'honoured' => array_sum(array_column($rows, 'honoured')),
                'overdue' => array_sum(array_column($rows, 'overdue')),
            ],
        ];
    }

    /**
     * Reps this viewer may report on. An admin sees every active rep; anyone
     * else only those in the regions they cover, and an empty territory means
     * nobody rather than everybody.
     *
     * @return EloquentCollection<int, User>
     */
    private function repsVisibleTo(User $viewer): EloquentCollection
    {
        return $this->eagerLoad(
            $viewer->isAdmin()
                ? $this->users->activeOfRole(UserRole::Mr)
                : $this->users->activeOfRoleInRegions(UserRole::Mr, $this->assignment->idsFor($viewer)),
            'region',
        );
    }

    /**
     * Telecallers this viewer may report on. A telecaller gets exactly one
     * row — their own: a peer's conversion rate is none of their business.
     *
     * @return EloquentCollection<int, User>
     */
    private function telecallersVisibleTo(User $viewer): EloquentCollection
    {
        return $this->eagerLoad(
            $viewer->isAdmin()
                ? $this->users->activeOfRole(UserRole::Telecaller)
                : $viewer->newCollection([$viewer]),
            'regions',
        );
    }

    /**
     * Eager-load a relation on users that arrived through a service contract.
     *
     * The contract returns a **Support** collection, and its empty path really
     * does hand back the base class — which has no `load()`. Re-wrapping is
     * what makes "a telecaller with no regions" behave like every other case
     * instead of a 500.
     *
     * @param  Collection<int, User>  $users
     * @return EloquentCollection<int, User>
     */
    private function eagerLoad($users, string $relation): EloquentCollection
    {
        return EloquentCollection::make($users->all())->load($relation);
    }

    /** @return array{date_from: string, date_to: string} */
    private function range(array $filters): array
    {
        return [
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ];
    }

    private function parse(?string $date): ?Carbon
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            // A hand-edited query string is not worth a 500 on a report.
            return null;
        }
    }

    /** Whole percent, or null when there is nothing to divide by. */
    private function percent(int $part, int $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }

    private function hours(int $minutes): float
    {
        return round($minutes / 60, 1);
    }

    /**
     * Sort by a column the report offers, descending, numbers first. Unknown
     * keys fall back to name so a stale bookmark still renders.
     */
    private function sortRows(array $rows, string $sort): array
    {
        if ($rows === [] || ! array_key_exists($sort, $rows[0])) {
            usort($rows, fn (array $a, array $b) => strcasecmp((string) $a['name'], (string) $b['name']));

            return $rows;
        }

        usort($rows, function (array $a, array $b) use ($sort) {
            $x = $a[$sort];
            $y = $b[$sort];

            return is_numeric($x) && is_numeric($y)
                ? $y <=> $x
                : strcasecmp((string) $x, (string) $y);
        });

        return $rows;
    }
}
