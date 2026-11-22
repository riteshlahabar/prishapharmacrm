<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Regions\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * The panel's presence report. Read-only — rows arrive from the MR app.
 *
 * Scoping is asserted on the view's `rows`, not on the rendered names: the
 * page also renders an MR filter dropdown listing every active rep, as every
 * other panel report does, so a name in the HTML is not evidence that the
 * viewer can read that rep's attendance.
 */
class AttendancePanelTest extends TestCase
{
    /** @return array<int, int> mr_ids the viewer actually got rows for */
    private function repIdsOnPage(LengthAwarePaginator $rows): array
    {
        return collect($rows->items())->pluck('mr_id')->unique()->sort()->values()->all();
    }

    public function test_an_admin_sees_the_report(): void
    {
        $mr = $this->mr(Region::factory()->create(), ['name' => 'Asha Rep']);
        Attendance::factory()->forMr($mr)->create();

        $this->actingAs($this->admin())->get('/attendance')
            ->assertOk()
            ->assertSee('Asha Rep')
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->total() === 1);
    }

    public function test_the_report_defaults_to_today(): void
    {
        $today = $this->mr();
        $older = $this->mr();

        Attendance::factory()->forMr($today)->create();
        Attendance::factory()->forMr($older)->daysAgo(5)->create();

        $this->actingAs($this->admin())->get('/attendance')
            ->assertOk()
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $this->repIdsOnPage($rows) === [$today->id]);
    }

    public function test_an_earlier_day_can_be_asked_for(): void
    {
        $mr = $this->mr();
        $day = now()->subDays(5)->toDateString();
        Attendance::factory()->forMr($mr)->daysAgo(5)->create();

        $this->actingAs($this->admin())
            ->get("/attendance?date_from={$day}&date_to={$day}")
            ->assertOk()
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->total() === 1);
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/attendance')->assertForbidden();
    }

    public function test_a_telecaller_without_the_permission_is_refused(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        // attendance.view is not a telecaller default (PermissionCatalog).
        $this->actingAs($telecaller)->get('/attendance')->assertForbidden();
    }

    /**
     * `attendance.view` is grantable, so an admin may hand it to a telecaller.
     * When they do the report stays inside the regions that telecaller covers —
     * the rule every other panel list already kept.
     */
    public function test_a_granted_telecaller_sees_only_reps_in_their_own_regions(): void
    {
        $mine = Region::factory()->named('Mine', 'MINE')->create();
        $theirs = Region::factory()->named('Theirs', 'THRS')->create();

        $telecaller = $this->telecaller([$mine]);
        $this->grant($telecaller, 'attendance.view');

        $inScope = $this->mr($mine);
        $outOfScope = $this->mr($theirs);
        Attendance::factory()->forMr($inScope)->create();
        Attendance::factory()->forMr($outOfScope)->create();

        $this->actingAs($telecaller)->get('/attendance')
            ->assertOk()
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $this->repIdsOnPage($rows) === [$inScope->id]);
    }

    public function test_the_counters_are_clamped_to_the_same_territory(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();

        $telecaller = $this->telecaller([$mine]);
        $this->grant($telecaller, 'attendance.view');

        Attendance::factory()->forMr($this->mr($mine))->create();
        Attendance::factory()->forMr($this->mr($theirs))->create();
        Attendance::factory()->forMr($this->mr($theirs))->create();

        // A summary counting all three would hand the viewer a company total.
        $this->actingAs($telecaller)->get('/attendance')
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => ! in_array(3, $summary, true));
    }

    /** An empty territory means no rows, never every row. */
    public function test_a_granted_telecaller_with_no_region_sees_nothing(): void
    {
        $telecaller = $this->telecaller();
        $this->grant($telecaller, 'attendance.view');

        Attendance::factory()->forMr($this->mr(Region::factory()->create()))->create();

        $this->actingAs($telecaller)->get('/attendance')
            ->assertOk()
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->total() === 0);
    }

    public function test_an_admin_is_not_clamped(): void
    {
        $a = Region::factory()->create();
        $b = Region::factory()->create();

        Attendance::factory()->forMr($this->mr($a))->create();
        Attendance::factory()->forMr($this->mr($b))->create();

        $this->actingAs($this->admin())->get('/attendance')
            ->assertOk()
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->total() === 2);
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/attendance')->assertRedirect(route('login'));
    }
}
