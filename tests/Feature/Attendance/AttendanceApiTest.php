<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Regions\Models\Region;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * `/api/v1/attendance/*` — the MR app's home screen (docs/api/v1-mr.md).
 *
 * Two rules carry the feature: the **server** stamps every time, so a phone
 * with a wound-back clock cannot forge a shift; and the day's sequence is
 * enforced, so a reinstall or a double tap cannot reopen a finished day.
 */
class AttendanceApiTest extends TestCase
{
    public function test_a_fresh_day_reports_a_usable_state_rather_than_an_error(): void
    {
        $mr = $this->mr(Region::factory()->create());

        $this->actingAsApp($mr)->getJson('/api/v1/attendance/today')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', null)
            ->assertJsonPath('data.date', now()->toDateString())
            ->assertJsonPath('data.status', 'Absent')
            ->assertJsonPath('data.can_check_in', true)
            ->assertJsonPath('data.can_check_out', false);
    }

    public function test_checking_in_creates_the_day_and_stamps_it_server_side(): void
    {
        Carbon::setTestNow('2026-11-22 09:30:00');
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/attendance/check-in', [
            'latitude' => 19.076,
            'longitude' => 72.8777,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'On duty')
            ->assertJsonPath('data.can_check_in', false)
            ->assertJsonPath('data.can_check_out', true)
            ->assertJsonPath('data.worked_minutes', null);

        $row = Attendance::firstOrFail();
        $this->assertSame($mr->id, $row->mr_id);
        $this->assertSame('2026-11-22', $row->date->toDateString());
        $this->assertSame('2026-11-22 09:30:00', $row->check_in_at->toDateTimeString());
        $this->assertSame('19.0760000', $row->check_in_lat);
    }

    /** The one rule the whole feature rests on. */
    public function test_a_device_cannot_supply_its_own_timestamp(): void
    {
        Carbon::setTestNow('2026-11-22 09:30:00');
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/attendance/check-in', [
            'latitude' => 19.076,
            'longitude' => 72.8777,
            // A tampered build trying to back-date a shift.
            'check_in_at' => '2026-11-01 06:00:00',
            'date' => '2026-11-01',
        ])->assertStatus(201);

        $row = Attendance::firstOrFail();
        $this->assertSame('2026-11-22 09:30:00', $row->check_in_at->toDateTimeString());
        $this->assertSame('2026-11-22', $row->date->toDateString());
    }

    public function test_checking_in_twice_is_a_conflict_not_a_second_row(): void
    {
        $mr = $this->mr();
        $token = $this->appTokenFor($mr);

        $this->actingAsApp($token)->postJson('/api/v1/attendance/check-in', [])->assertStatus(201);

        $this->actingAsApp($token)->postJson('/api/v1/attendance/check-in', [])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'already_checked_in');

        $this->assertSame(1, Attendance::count());
    }

    public function test_checking_out_closes_the_day_and_reports_the_time_worked(): void
    {
        Carbon::setTestNow('2026-11-22 09:00:00');
        $mr = $this->mr();
        $token = $this->appTokenFor($mr);

        $this->actingAsApp($token)->postJson('/api/v1/attendance/check-in', [])->assertStatus(201);

        Carbon::setTestNow('2026-11-22 17:30:00');

        $this->actingAsApp($token)->postJson('/api/v1/attendance/check-out', [
            'latitude' => 19.08,
            'longitude' => 72.88,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Completed')
            ->assertJsonPath('data.worked_minutes', 510)
            ->assertJsonPath('data.can_check_in', false)
            ->assertJsonPath('data.can_check_out', false);
    }

    public function test_checking_out_without_checking_in_is_refused(): void
    {
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/attendance/check-out', [])
            ->assertStatus(409)
            ->assertJsonPath('errors.reason', 'not_checked_in');
    }

    public function test_a_finished_day_cannot_be_reopened(): void
    {
        $mr = $this->mr();
        Attendance::factory()->forMr($mr)->create();
        $token = $this->appTokenFor($mr);

        $this->actingAsApp($token)->postJson('/api/v1/attendance/check-out', [])
            ->assertStatus(409)
            ->assertJsonPath('errors.reason', 'already_checked_out');

        $this->actingAsApp($token)->postJson('/api/v1/attendance/check-in', [])
            ->assertStatus(409)
            ->assertJsonPath('errors.reason', 'already_checked_in');
    }

    /** A punch with location permission refused still records the shift. */
    public function test_coordinates_are_optional(): void
    {
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/attendance/check-in', [])->assertStatus(201);

        $row = Attendance::firstOrFail();
        $this->assertNotNull($row->check_in_at);
        $this->assertNull($row->check_in_lat);
    }

    public function test_one_coordinate_without_the_other_is_rejected(): void
    {
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/attendance/check-in', ['latitude' => 19.076])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonPath('errors.longitude.0', 'Both coordinates are needed, or neither.');
    }

    public function test_coordinates_outside_the_globe_are_rejected(): void
    {
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/attendance/check-in', [
            'latitude' => 120,
            'longitude' => 200,
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['latitude', 'longitude']]);
    }

    public function test_history_shows_only_the_callers_own_days(): void
    {
        $region = Region::factory()->create();
        $mine = $this->mr($region);
        $theirs = $this->mr($region);

        Attendance::factory()->forMr($mine)->daysAgo(1)->create();
        Attendance::factory()->forMr($mine)->daysAgo(2)->create();
        Attendance::factory()->forMr($theirs)->daysAgo(1)->create();

        $response = $this->actingAsApp($mine)->getJson('/api/v1/attendance/history')->assertOk();

        $this->assertSame(2, $response->json('meta.total'));
    }

    public function test_history_can_be_narrowed_to_a_date_range(): void
    {
        Carbon::setTestNow('2026-11-22 12:00:00');
        $mr = $this->mr();

        Attendance::factory()->forMr($mr)->onDate('2026-11-10')->create();
        Attendance::factory()->forMr($mr)->onDate('2026-11-20')->create();
        Attendance::factory()->forMr($mr)->onDate('2026-11-21')->create();

        $this->actingAsApp($mr)
            ->getJson('/api/v1/attendance/history?date_from=2026-11-19&date_to=2026-11-21')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_the_telecaller_app_cannot_reach_attendance_at_all(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $token = $this->appTokenFor($telecaller);

        foreach (['today', 'history'] as $path) {
            $this->actingAsApp($token)->getJson("/api/v1/attendance/{$path}")
                ->assertStatus(403)
                ->assertJsonPath('errors.reason', 'forbidden');
        }

        foreach (['check-in', 'check-out'] as $path) {
            $this->actingAsApp($token)->postJson("/api/v1/attendance/{$path}", [])
                ->assertStatus(403);
        }
    }

    public function test_an_open_shift_reports_no_time_worked_rather_than_guessing(): void
    {
        $mr = $this->mr();
        $row = Attendance::factory()->forMr($mr)->openShift()->create();

        $this->assertNull($row->workedMinutes());
        $this->assertSame('—', $row->workedLabel());
        $this->assertSame('On duty', $row->status());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
