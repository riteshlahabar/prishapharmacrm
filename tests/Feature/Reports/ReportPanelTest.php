<?php

namespace Tests\Feature\Reports;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Messages\Models\Message;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Models\Visit;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The six management reports (Phase 11). The module owns no table: every
 * figure arrives through a sibling module's `…ForUser` contract, so these
 * tests are as much about the clamp as about the arithmetic.
 */
class ReportPanelTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function reportSlugs(): array
    {
        return [
            'mr performance' => ['mr'],
            'telecaller performance' => ['telecaller'],
            'coverage gaps' => ['coverage'],
            'region rollup' => ['region'],
            'attendance register' => ['register'],
            'follow-up compliance' => ['follow-ups'],
        ];
    }

    #[DataProvider('reportSlugs')]
    public function test_every_report_renders_for_an_admin(string $slug): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $telecaller = $this->telecaller([$region]);
        $person = Person::factory()->inRegion($region)->assignedToMr($mr)->create();

        Attendance::factory()->forMr($mr)->create();
        Visit::factory()->forMr($mr)->forPerson($person)->create();
        CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->followUpDue()->create();
        Message::factory()->from($telecaller)->to($mr)->create();

        $this->actingAs($this->admin())->get("/reports/{$slug}")->assertOk();
    }

    #[DataProvider('reportSlugs')]
    public function test_every_report_renders_with_no_data_at_all(string $slug): void
    {
        // A fresh install must not blow up on an empty table.
        $this->actingAs($this->admin())->get("/reports/{$slug}")->assertOk();
    }

    public function test_the_index_lists_the_reports(): void
    {
        $this->actingAs($this->admin())->get('/reports')
            ->assertOk()
            ->assertSee('Medical rep performance')
            ->assertSee('Telecaller performance')
            ->assertSee('Coverage gaps')
            ->assertSee('Attendance register');
    }

    public function test_an_unknown_report_is_not_found(): void
    {
        $this->actingAs($this->admin())->get('/reports/not-a-report')->assertNotFound();
    }

    public function test_the_period_defaults_to_the_last_thirty_days(): void
    {
        Carbon::setTestNow('2026-11-22 10:00:00');

        $this->actingAs($this->admin())->get('/reports/mr')
            ->assertOk()
            ->assertViewHas('filters', fn (array $f) => $f['date_from'] === '2026-10-24'
                && $f['date_to'] === '2026-11-22'
                && $f['days'] === 30);
    }

    public function test_a_backwards_date_range_is_swapped_rather_than_rejected(): void
    {
        $this->actingAs($this->admin())
            ->get('/reports/mr?date_from=2026-11-20&date_to=2026-11-01')
            ->assertOk()
            ->assertViewHas('filters', fn (array $f) => $f['date_from'] === '2026-11-01'
                && $f['date_to'] === '2026-11-20');
    }

    public function test_a_nonsense_date_falls_back_to_the_default(): void
    {
        $this->actingAs($this->admin())
            ->get('/reports/mr?date_from=not-a-date&date_to=also-not')
            ->assertOk();
    }

    // ---------------------------------------------------------------- figures

    public function test_the_mr_report_counts_presence_visits_and_coverage(): void
    {
        Carbon::setTestNow('2026-11-22 12:00:00');

        $region = Region::factory()->create();
        $mr = $this->mr($region, ['name' => 'Asha Rep']);

        // Four assigned contacts, two of them actually visited.
        $seen = Person::factory()->count(2)->inRegion($region)->assignedToMr($mr)->create();
        Person::factory()->count(2)->inRegion($region)->assignedToMr($mr)->create();

        Attendance::factory()->forMr($mr)->daysAgo(1)->create();
        Attendance::factory()->forMr($mr)->daysAgo(2)->create();

        Visit::factory()->forMr($mr)->forPerson($seen[0])->daysAgo(1)->create();
        Visit::factory()->forMr($mr)->forPerson($seen[1])->daysAgo(1)->create();
        Visit::factory()->forMr($mr)->forPerson($seen[0])->daysAgo(2)->create();

        $row = $this->reportRow('mr', $this->admin(), 'Asha Rep');

        $this->assertSame(2, $row['days_present']);
        $this->assertSame(3, $row['visits']);
        $this->assertSame(2, $row['persons_seen']);
        $this->assertSame(4, $row['assigned']);
        // 2 of 4 assigned contacts reached.
        $this->assertSame(50, $row['coverage']);
        // 3 visits across 2 days actually worked.
        $this->assertSame(1.5, $row['visits_per_day']);
    }

    public function test_a_rep_with_no_activity_still_gets_a_row(): void
    {
        $region = Region::factory()->create();
        $this->mr($region, ['name' => 'Idle Rep']);

        $row = $this->reportRow('mr', $this->admin(), 'Idle Rep');

        $this->assertSame(0, $row['days_present']);
        $this->assertSame(0, $row['visits']);
        // Nothing to divide by, so null rather than a misleading zero.
        $this->assertNull($row['visits_per_day']);
    }

    public function test_the_telecaller_report_counts_calls_conversions_and_messages(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region], ['name' => 'Priya Caller']);
        $mr = $this->mr($region);
        $person = Person::factory()->inRegion($region)->create();

        CallLog::factory()->count(3)->forTelecaller($telecaller)->forPerson($person)->create();
        CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->converted()->create();
        CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->followUpDue()->create();
        Message::factory()->count(2)->from($telecaller)->to($mr)->create();

        $row = $this->reportRow('telecaller', $this->admin(), 'Priya Caller');

        $this->assertSame(5, $row['calls']);
        $this->assertSame(1, $row['converted']);
        $this->assertSame(20, $row['conversion']);
        $this->assertSame(1, $row['overdue']);
        $this->assertSame(2, $row['messages_sent']);
    }

    public function test_the_coverage_report_finds_the_neglected_and_ignores_the_rest(): void
    {
        Carbon::setTestNow('2026-11-22 12:00:00');

        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $telecaller = $this->telecaller([$region]);

        $fresh = Person::factory()->inRegion($region)->assignedToMr($mr)->create(['name' => 'Seen Recently']);
        $stale = Person::factory()->inRegion($region)->assignedToMr($mr)->create(['name' => 'Seen Long Ago']);
        Person::factory()->inRegion($region)->assignedToMr($mr)->create(['name' => 'Never Touched']);
        Person::factory()->inRegion($region)->inactive()->create(['name' => 'Switched Off']);

        Visit::factory()->forMr($mr)->forPerson($fresh)->daysAgo(2)->create();
        CallLog::factory()->forTelecaller($telecaller)->forPerson($fresh)->calledDaysAgo(2)->create();
        Visit::factory()->forMr($mr)->forPerson($stale)->daysAgo(90)->create();

        $response = $this->actingAs($this->admin())->get('/reports/coverage')->assertOk();
        $names = array_column($response->viewData('data')['rows'], 'name');

        $this->assertContains('Never Touched', $names);
        $this->assertContains('Seen Long Ago', $names);
        $this->assertNotContains('Seen Recently', $names, 'touched inside the window is not a gap');
        $this->assertNotContains('Switched Off', $names, 'a deactivated contact is not a gap');
    }

    public function test_the_never_touched_sort_to_the_top(): void
    {
        Carbon::setTestNow('2026-11-22 12:00:00');

        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $stale = Person::factory()->inRegion($region)->create(['name' => 'Stale']);
        Person::factory()->inRegion($region)->create(['name' => 'Never']);

        Visit::factory()->forMr($mr)->forPerson($stale)->daysAgo(60)->create();

        $rows = $this->actingAs($this->admin())->get('/reports/coverage')
            ->assertOk()->viewData('data')['rows'];

        $this->assertSame('Never', $rows[0]['name']);
    }

    public function test_the_region_report_rolls_up_headcount_and_activity(): void
    {
        $region = Region::factory()->named('Pune West', 'PNW')->create();
        $mr = $this->mr($region);
        $telecaller = $this->telecaller([$region]);
        $person = Person::factory()->inRegion($region)->create();

        Visit::factory()->count(2)->forMr($mr)->forPerson($person)->create();
        CallLog::factory()->count(3)->forTelecaller($telecaller)->forPerson($person)->create();

        $row = $this->reportRow('region', $this->admin(), 'Pune West');

        $this->assertSame(1, $row['mrs']);
        $this->assertSame(1, $row['telecallers']);
        $this->assertSame(1, $row['persons']);
        $this->assertSame(2, $row['visits']);
        $this->assertSame(3, $row['calls']);
        $this->assertSame(5.0, $row['touches_per_person']);
    }

    public function test_the_register_marks_worked_open_and_absent_days(): void
    {
        Carbon::setTestNow('2026-11-22 12:00:00');

        $region = Region::factory()->create();
        $mr = $this->mr($region, ['name' => 'Register Rep']);

        Attendance::factory()->forMr($mr)->onDate('2026-11-20')->create();
        Attendance::factory()->forMr($mr)->onDate('2026-11-21')->openShift()->create();

        $data = $this->actingAs($this->admin())
            ->get('/reports/register?date_from=2026-11-20&date_to=2026-11-22')
            ->assertOk()->viewData('data');

        $row = collect($data['rows'])->firstWhere('name', 'Register Rep');

        $this->assertCount(3, $data['days']);
        // Worked and closed, still on duty, then a day with no row at all.
        $this->assertSame(['P', 'O', 'A'], $row['cells']);
        $this->assertSame(2, $row['present']);
        $this->assertSame(1, $row['absent']);
    }

    public function test_follow_up_compliance_separates_punctual_from_merely_honoured(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region], ['name' => 'Compliance Caller']);
        $person = Person::factory()->inRegion($region)->create();

        // Promised and closed before the date promised.
        CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->create([
            'status' => 'callback',
            'follow_up_at' => now()->addDays(3),
            'follow_up_done_at' => now()->addDay(),
        ]);
        // Promised, closed — but after the date promised.
        CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->create([
            'status' => 'callback',
            'follow_up_at' => now()->subDays(5),
            'follow_up_done_at' => now()->subDay(),
        ]);
        // Promised, still open, date gone.
        CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->followUpDue()->create();

        $row = $this->reportRow('follow-ups', $this->admin(), 'Compliance Caller');

        $this->assertSame(3, $row['promised']);
        $this->assertSame(2, $row['honoured']);
        $this->assertSame(1, $row['on_time']);
        $this->assertSame(1, $row['late']);
        $this->assertSame(1, $row['overdue']);
        $this->assertSame(67, $row['kept']);
        // The figure that actually matters: kept by the date promised.
        $this->assertSame(33, $row['punctual']);
    }

    public function test_a_telecaller_who_promised_nothing_is_left_off_the_compliance_report(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region], ['name' => 'No Promises']);
        $person = Person::factory()->inRegion($region)->create();

        CallLog::factory()->count(3)->forTelecaller($telecaller)->forPerson($person)->create();

        $rows = $this->actingAs($this->admin())->get('/reports/follow-ups')
            ->assertOk()->viewData('data')['rows'];

        $this->assertNotContains('No Promises', array_column($rows, 'name'));
    }

    public function test_a_report_can_be_sorted_by_a_column(): void
    {
        $region = Region::factory()->create();
        $busy = $this->mr($region, ['name' => 'Busy Rep']);
        $quiet = $this->mr($region, ['name' => 'Aaa Quiet Rep']);
        $person = Person::factory()->inRegion($region)->create();

        Visit::factory()->count(3)->forMr($busy)->forPerson($person)->create();
        Visit::factory()->forMr($quiet)->forPerson($person)->create();

        $rows = $this->actingAs($this->admin())->get('/reports/mr?sort=visits')
            ->assertOk()->viewData('data')['rows'];

        $this->assertSame('Busy Rep', $rows[0]['name'], 'most visits first');
    }

    // ----------------------------------------------------------- the clamp

    public function test_a_telecaller_sees_only_reps_in_their_own_regions(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();

        $telecaller = $this->telecaller([$mine]);
        $this->mr($mine, ['name' => 'Rep In Scope']);
        $this->mr($theirs, ['name' => 'Rep Out Of Scope']);

        $names = array_column(
            $this->actingAs($telecaller)->get('/reports/mr')->assertOk()->viewData('data')['rows'],
            'name',
        );

        $this->assertSame(['Rep In Scope'], $names);
    }

    /** A peer's conversion rate is none of a telecaller's business. */
    public function test_a_telecaller_sees_only_their_own_row_on_the_telecaller_report(): void
    {
        $region = Region::factory()->create();
        $me = $this->telecaller([$region], ['name' => 'Me Myself']);
        $peer = $this->telecaller([$region], ['name' => 'My Peer']);
        $person = Person::factory()->inRegion($region)->create();

        CallLog::factory()->count(5)->forTelecaller($peer)->forPerson($person)->create();
        CallLog::factory()->forTelecaller($me)->forPerson($person)->create();

        $data = $this->actingAs($me)->get('/reports/telecaller')->assertOk()->viewData('data');

        $this->assertSame(['Me Myself'], array_column($data['rows'], 'name'));
        $this->assertSame(1, $data['rows'][0]['calls'], "a peer's calls must not be counted in");
    }

    public function test_a_telecaller_sees_coverage_gaps_only_in_their_own_regions(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();
        $telecaller = $this->telecaller([$mine]);

        Person::factory()->inRegion($mine)->create(['name' => 'Gap In Scope']);
        Person::factory()->inRegion($theirs)->create(['name' => 'Gap Out Of Scope']);

        $names = array_column(
            $this->actingAs($telecaller)->get('/reports/coverage')->assertOk()->viewData('data')['rows'],
            'name',
        );

        $this->assertSame(['Gap In Scope'], $names);
    }

    public function test_a_telecaller_sees_only_their_own_regions_on_the_region_report(): void
    {
        $mine = Region::factory()->named('Mine', 'MINE')->create();
        Region::factory()->named('Theirs', 'THRS')->create();

        $names = array_column(
            $this->actingAs($this->telecaller([$mine]))->get('/reports/region')
                ->assertOk()->viewData('data')['rows'],
            'name',
        );

        $this->assertSame(['Mine'], $names);
    }

    public function test_a_telecaller_sees_only_their_own_regions_reps_on_the_register(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();
        $telecaller = $this->telecaller([$mine]);

        $this->mr($mine, ['name' => 'Mine Rep']);
        $this->mr($theirs, ['name' => 'Their Rep']);

        $names = array_column(
            $this->actingAs($telecaller)->get('/reports/register')->assertOk()->viewData('data')['rows'],
            'name',
        );

        $this->assertSame(['Mine Rep'], $names);
    }

    /** An empty territory means nobody, never everybody. */
    public function test_a_telecaller_with_no_region_sees_nothing(): void
    {
        $telecaller = $this->telecaller();
        $region = Region::factory()->create();
        $this->mr($region, ['name' => 'Somebody Else']);
        Person::factory()->inRegion($region)->create();

        foreach (['mr', 'coverage', 'region', 'register'] as $slug) {
            $rows = $this->actingAs($telecaller)->get("/reports/{$slug}")
                ->assertOk()->viewData('data')['rows'];

            $this->assertSame([], $rows, "{$slug} should be empty for an unassigned telecaller");
        }
    }

    public function test_the_region_picker_offers_a_scoped_viewer_only_their_own_regions(): void
    {
        $mine = Region::factory()->named('Mine', 'MINE')->create();
        Region::factory()->named('Theirs', 'THRS')->create();

        $this->actingAs($this->telecaller([$mine]))->get('/reports/mr')
            ->assertOk()
            ->assertViewHas('regionOptions', fn ($options) => $options->pluck('name')->all() === ['Mine']);
    }

    // ------------------------------------------------------- authorisation

    public function test_a_telecaller_reaches_the_reports_by_default(): void
    {
        $this->actingAs($this->telecaller([Region::factory()->create()]))
            ->get('/reports')->assertOk();
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/reports')->assertForbidden();
        $this->actingAs($this->mr())->get('/reports/mr')->assertForbidden();
    }

    public function test_a_viewer_without_the_permission_is_refused(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->revoke($telecaller, 'reports.view');

        $this->actingAs($telecaller)->get('/reports')->assertForbidden();
        $this->actingAs($telecaller)->get('/reports/mr')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/reports')->assertRedirect(route('login'));
        $this->get('/reports/mr')->assertRedirect(route('login'));
    }

    /** @return array<string, mixed> */
    private function reportRow(string $slug, $viewer, string $name): array
    {
        $rows = $this->actingAs($viewer)->get("/reports/{$slug}")
            ->assertOk()->viewData('data')['rows'];

        $row = collect($rows)->firstWhere('name', $name);

        $this->assertNotNull($row, "no row for {$name} on the {$slug} report");

        return $row;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
