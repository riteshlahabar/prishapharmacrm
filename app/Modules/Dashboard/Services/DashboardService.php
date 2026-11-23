<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Attendance\Services\Contracts\AttendanceServiceInterface;
use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;
use App\Modules\Dashboard\Services\Contracts\DashboardServiceInterface;
use App\Modules\Messages\Models\Message;
use App\Modules\Messages\Services\Contracts\MessageServiceInterface;
use App\Modules\Persons\Models\Person;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use App\Modules\Visits\Models\Visit;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * One read model for one screen, assembled from the other modules'
 * contracts. Two rules shape it.
 *
 * **It asks before it reads.** Each section is gated on that module's own
 * policy (`viewAny`), so the dashboard holds no copy of who-may-see-what: an
 * admin gets every card, a telecaller gets the four they have permission for,
 * and a card nobody may see costs no queries at all.
 *
 * **Scope is the services' job.** Every call is a `…ForUser` variant, so the
 * numbers are clamped where the rule lives rather than here.
 */
class DashboardService implements DashboardServiceInterface
{
    /**
     * Cuba badge name => the hex ApexCharts needs, since a canvas cannot read
     * a CSS class. `primary` is the theme default (`--theme-default`), now
     * the Prisha teal; the rest are Bootstrap's, which is what Cuba's badges
     * resolve to.
     */
    private const BADGE_HEX = [
        'primary' => '#00797C',
        'secondary' => '#6C757D',
        'success' => '#198754',
        'danger' => '#DC3545',
        'warning' => '#FFC107',
        'info' => '#0DCAF0',
    ];

    public function __construct(
        private readonly VisitServiceInterface $visits,
        private readonly CallLogServiceInterface $calls,
        private readonly AttendanceServiceInterface $attendance,
        private readonly MessageServiceInterface $messages,
        private readonly PersonServiceInterface $persons,
        private readonly UserServiceInterface $users,
        private readonly RegionAssignmentServiceInterface $assignment,
    ) {}

    public function period(mixed $days): int
    {
        $days = is_numeric($days) ? (int) $days : self::DEFAULT_PERIOD;

        return in_array($days, self::PERIODS, true) ? $days : self::DEFAULT_PERIOD;
    }

    /** @return array<string, mixed> */
    public function forUser(User $user, int $days = self::DEFAULT_PERIOD): array
    {
        $days = $this->period($days);
        $to = Carbon::today();
        $from = $to->copy()->subDays($days - 1);

        // The period filter every module understands.
        $range = ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()];

        $can = [
            'visits' => Gate::forUser($user)->allows('viewAny', Visit::class),
            'calls' => Gate::forUser($user)->allows('viewAny', CallLog::class),
            'attendance' => Gate::forUser($user)->allows('viewAny', Attendance::class),
            'messages' => Gate::forUser($user)->allows('viewAny', Message::class),
            'persons' => Gate::forUser($user)->allows('viewAny', Person::class),
        ];

        $visits = $can['visits'] ? $this->visits->summaryForUser($user, $range) : null;
        $calls = $can['calls'] ? $this->calls->summaryForUser($user, $range) : null;
        $today = $can['attendance']
            ? $this->attendance->summaryForUser($user, ['date_from' => $to->toDateString(), 'date_to' => $to->toDateString()])
            : null;

        return [
            'days' => $days,
            'periods' => self::PERIODS,
            'from' => $from,
            'to' => $to,
            'can' => $can,
            'regions' => $this->assignment->regionsFor($user),
            'tiles' => $this->tiles($user, $can, $range, $visits, $calls, $today),
            'charts' => $this->charts($user, $can, $range, $from, $to, $calls),
            // Nothing to show at all — a fresh install, or a viewer who covers
            // no region yet. The view says so instead of drawing empty axes.
            'empty' => ($visits['total'] ?? 0) === 0 && ($calls['total'] ?? 0) === 0,
        ];
    }

    /**
     * Headline numbers. Each entry is ready to render: label, value, an
     * optional sub-line, a feather icon name, the accent it is painted in
     * (`.stat-tile.tile-<colour>` in assets/css/prisha.css) and a link to the
     * screen behind it, so the view holds no logic. The palette is decided
     * here, once, rather than by position in the loop: a telecaller sees a
     * shorter list, and the colours should not shuffle because of it.
     *
     * @return array<int, array<string, mixed>>
     */
    private function tiles(
        User $user,
        array $can,
        array $range,
        ?array $visits,
        ?array $calls,
        ?array $today,
    ): array {
        $tiles = [];

        if ($can['persons']) {
            $tiles[] = [
                'label' => 'Clients',
                'value' => $this->persons->countForUser($user, ['status' => 'active']),
                'sub' => $user->isAdmin() ? 'active, all regions' : 'active, your regions',
                'icon' => 'users',
                'colour' => 'teal',
                'route' => 'clients.index',
            ];
        }

        if ($can['attendance'] && $today !== null) {
            // "3 of 8" needs the headcount, which attendance rows cannot give:
            // a rep who never checked in has no row to count.
            $reps = $this->users->countActiveInRole(
                UserRole::Mr,
                $user->isAdmin() ? [] : $this->assignment->idsFor($user),
            );
            $present = $today['on_duty'] + $today['completed'];

            $tiles[] = [
                'label' => 'Checked in',
                'value' => $reps > 0 ? "{$present} of {$reps}" : '—',
                'sub' => $today['on_duty'].' still on duty',
                'icon' => 'clock',
                'colour' => 'green',
                'route' => 'attendance.index',
            ];
        }

        if ($can['visits'] && $visits !== null) {
            $tiles[] = [
                'label' => 'Visits',
                'value' => $visits['total'],
                'sub' => $visits['persons'].' '.Str::plural('client', $visits['persons'])
                    .' · '.$visits['geotagged'].' geotagged',
                'icon' => 'map-pin',
                'colour' => 'blue',
                'route' => 'visits.index',
            ];
        }

        if ($can['calls'] && $calls !== null) {
            $tiles[] = [
                'label' => 'Calls',
                'value' => $calls['total'],
                'sub' => $calls['follow_ups_open'].' '.Str::plural('call-back', $calls['follow_ups_open']).' open',
                'icon' => 'phone-call',
                'colour' => 'amber',
                'route' => 'calls.index',
            ];
            $tiles[] = [
                'label' => 'Conversion',
                'value' => $this->rate($calls['converted'], $calls['total']).'%',
                'sub' => $calls['converted'].' of '.$calls['total'].' converted',
                'icon' => 'trending-up',
                'colour' => 'violet',
                'route' => 'calls.index',
            ];
        }

        if ($can['messages']) {
            $sent = $this->messages->summaryForUser($user, $range);
            $tiles[] = [
                'label' => 'Messages',
                'value' => $sent['total'],
                'sub' => $sent['unread'].' still unread',
                'icon' => 'message-square',
                'colour' => 'rose',
                'route' => 'messages.index',
            ];
        }

        return $tiles;
    }

    /** @return array<string, mixed> */
    private function charts(
        User $user,
        array $can,
        array $range,
        Carbon $from,
        Carbon $to,
        ?array $calls,
    ): array {
        $labels = $this->dayRange($from, $to);

        $charts = ['labels' => $labels, 'series' => []];

        // Activity over the period: one chart, a series per thing the viewer
        // may see, so a telecaller gets calls alone rather than an empty axis.
        if ($can['visits']) {
            $charts['series'][] = [
                'name' => 'Visits',
                'data' => $this->fill($this->visits->trendForUser($user, $range), $labels),
            ];
        }

        if ($can['calls']) {
            $charts['series'][] = [
                'name' => 'Calls',
                'data' => $this->fill($this->calls->trendForUser($user, $range), $labels),
            ];
        }

        if ($can['attendance']) {
            $charts['attendance'] = [
                'name' => 'Reps present',
                'data' => $this->fill($this->attendance->trendForUser($user, $range), $labels),
            ];
        }

        // Calls by status, every status present so the colours never shift
        // between two renders of the same chart.
        if ($can['calls'] && $calls !== null) {
            $byStatus = [];
            $colours = [];

            foreach (CallStatus::cases() as $status) {
                $byStatus[$status->label()] = (int) ($calls['by_status'][$status->value] ?? 0);
                // Through the enum's own badge name, so a slice here is the
                // colour that status already wears on the call report.
                $colours[] = self::BADGE_HEX[$status->badge()] ?? self::BADGE_HEX['secondary'];
            }

            $charts['calls_by_status'] = [
                'labels' => array_keys($byStatus),
                'data' => array_values($byStatus),
                'colours' => $colours,
                'total' => array_sum($byStatus),
            ];
        }

        if ($can['visits']) {
            $byMr = $this->visits->countsByMrForUser($user, $range, 8);

            $charts['visits_by_mr'] = [
                'labels' => array_keys($byMr),
                'data' => array_map(intval(...), array_values($byMr)),
            ];
        }

        return $charts;
    }

    /**
     * Every day in the period, so a gap in the data reads as a zero rather
     * than closing up and making two weeks look like three days.
     *
     * @return array<int, string>
     */
    private function dayRange(Carbon $from, Carbon $to): array
    {
        $days = [];

        for ($day = $from->copy(); $day->lessThanOrEqualTo($to); $day->addDay()) {
            $days[] = $day->toDateString();
        }

        return $days;
    }

    /**
     * @param  array<string, mixed>  $trend
     * @param  array<int, string>  $labels
     * @return array<int, int>
     */
    private function fill(array $trend, array $labels): array
    {
        return array_map(fn (string $day) => (int) ($trend[$day] ?? 0), $labels);
    }

    private function rate(int $part, int $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }
}
