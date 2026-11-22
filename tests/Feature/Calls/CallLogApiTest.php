<?php

namespace Tests\Feature\Calls;

use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * `/api/v1/call-logs` — the telecaller app's working record
 * (docs/api/v1-telecaller.md).
 *
 * A call log is scoped by its **caller**, not by territory: notes are a
 * personal record, so `CallLogPolicy::update` has no admin bypass. Another
 * telecaller's row reads as 404, never 403.
 */
class CallLogApiTest extends TestCase
{
    private Region $region;

    private User $telecaller;

    private Person $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create();
        $this->telecaller = $this->telecaller([$this->region]);
        $this->person = Person::factory()->inRegion($this->region)->create(['name' => 'Dr Iyer']);
    }

    public function test_a_telecaller_logs_a_call(): void
    {
        Carbon::setTestNow('2026-11-22 14:00:00');

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'interested',
            'notes' => 'Asked for a sample.',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'interested')
            ->assertJsonPath('data.person.name', 'Dr Iyer');

        $call = CallLog::firstOrFail();
        $this->assertSame($this->telecaller->id, $call->telecaller_id);
        $this->assertSame('2026-11-22 14:00:00', $call->called_at->toDateTimeString());
    }

    /** `called_at` is server-set, so work cannot be back-dated from a device. */
    public function test_the_call_time_cannot_be_supplied_by_the_device(): void
    {
        Carbon::setTestNow('2026-11-22 14:00:00');

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'interested',
            'called_at' => '2026-01-01 08:00:00',
            'telecaller_id' => $this->telecaller->id + 99,
        ])->assertStatus(201);

        $call = CallLog::firstOrFail();
        $this->assertSame('2026-11-22 14:00:00', $call->called_at->toDateTimeString());
        $this->assertSame($this->telecaller->id, $call->telecaller_id);
    }

    public function test_a_person_outside_the_callers_regions_is_refused(): void
    {
        $elsewhere = Person::factory()->inRegion(Region::factory()->create())->create();

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $elsewhere->id,
            'status' => 'interested',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonPath('errors.person_id.0', 'That person is not on your active list.');
    }

    public function test_a_deactivated_person_is_refused(): void
    {
        $person = Person::factory()->inRegion($this->region)->inactive()->create();

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $person->id,
            'status' => 'interested',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.person_id.0', 'That person is not on your active list.');
    }

    public function test_a_telecaller_with_no_region_can_call_nobody(): void
    {
        $unassigned = $this->telecaller();

        $this->actingAsApp($unassigned)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'interested',
        ])->assertStatus(422);
    }

    /** The one awkward rule both apps mirror. */
    public function test_a_callback_requires_a_follow_up_date(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'callback',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.follow_up_at.0', 'A call-back needs a date to come back on.');
    }

    public function test_any_other_status_refuses_a_follow_up_date(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'interested',
            'follow_up_at' => now()->addDay()->toDateTimeString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.follow_up_at.0', 'Only a call-back carries a follow-up date.');
    }

    public function test_a_follow_up_date_must_be_in_the_future(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'callback',
            'follow_up_at' => now()->subDay()->toDateTimeString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.follow_up_at.0', 'The follow-up date has to be in the future.');
    }

    public function test_a_valid_callback_is_accepted(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'callback',
            'follow_up_at' => now()->addDays(2)->toDateTimeString(),
        ])->assertStatus(201);

        $this->assertTrue(CallLog::firstOrFail()->hasOpenFollowUp());
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'maybe',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_the_history_shows_only_the_callers_own_calls(): void
    {
        $other = $this->telecaller([$this->region]);

        CallLog::factory()->count(2)->forTelecaller($this->telecaller)->forPerson($this->person)->create();
        CallLog::factory()->count(3)->forTelecaller($other)->forPerson($this->person)->create();

        $this->actingAsApp($this->telecaller)->getJson('/api/v1/call-logs')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_the_history_can_be_filtered_by_status(): void
    {
        CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->converted()->create();
        CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->create();

        $this->actingAsApp($this->telecaller)->getJson('/api/v1/call-logs?status=converted')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_a_telecaller_amends_their_own_call(): void
    {
        $call = CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->create();

        $this->actingAsApp($this->telecaller)->patchJson("/api/v1/call-logs/{$call->id}", [
            'notes' => 'Updated after a second conversation.',
            'status' => 'converted',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'converted')
            ->assertJsonPath('data.notes', 'Updated after a second conversation.');
    }

    /** Missing, not forbidden — a 403 would confirm the row exists. */
    public function test_another_telecallers_call_reads_as_missing(): void
    {
        $other = $this->telecaller([$this->region]);
        $theirs = CallLog::factory()->forTelecaller($other)->forPerson($this->person)->create();

        $this->actingAsApp($this->telecaller)->patchJson("/api/v1/call-logs/{$theirs->id}", ['notes' => 'x'])
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'not_found');
    }

    public function test_the_person_on_a_call_cannot_be_reassigned(): void
    {
        $call = CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->create();
        $someoneElse = Person::factory()->inRegion($this->region)->create();

        $this->actingAsApp($this->telecaller)->patchJson("/api/v1/call-logs/{$call->id}", [
            'person_id' => $someoneElse->id,
        ])->assertOk();

        // Re-pointing a call would rewrite history; the field is simply ignored.
        $this->assertSame($this->person->id, $call->refresh()->person_id);
    }

    public function test_moving_off_callback_clears_the_follow_up(): void
    {
        $call = CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->callback()->create();

        $this->actingAsApp($this->telecaller)->patchJson("/api/v1/call-logs/{$call->id}", [
            'status' => 'not_interested',
        ])->assertOk();

        $this->assertNull($call->refresh()->follow_up_at);
    }

    public function test_the_follow_up_queue_defaults_to_what_is_already_due(): void
    {
        CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->followUpDue()->create();
        CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->callback()->create();

        $this->actingAsApp($this->telecaller)->getJson('/api/v1/call-logs/follow-ups')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_queue_can_show_everything_still_promised(): void
    {
        CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->followUpDue()->create();
        CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->callback()->create();

        $this->actingAsApp($this->telecaller)->getJson('/api/v1/call-logs/follow-ups?due=0')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_a_closed_follow_up_leaves_the_queue(): void
    {
        $call = CallLog::factory()->forTelecaller($this->telecaller)->forPerson($this->person)->followUpDue()->create();

        $this->actingAsApp($this->telecaller)
            ->postJson("/api/v1/call-logs/{$call->id}/follow-up-done")
            ->assertOk();

        $this->assertNotNull($call->refresh()->follow_up_done_at);
        $this->assertFalse($call->hasOpenFollowUp());

        $this->actingAsApp($this->telecaller)->getJson('/api/v1/call-logs/follow-ups')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_another_telecaller_cannot_close_a_follow_up(): void
    {
        $other = $this->telecaller([$this->region]);
        $theirs = CallLog::factory()->forTelecaller($other)->forPerson($this->person)->followUpDue()->create();

        $this->actingAsApp($this->telecaller)
            ->postJson("/api/v1/call-logs/{$theirs->id}/follow-up-done")
            ->assertStatus(404);

        $this->assertNull($theirs->refresh()->follow_up_done_at);
    }

    public function test_the_mr_app_cannot_reach_call_logs(): void
    {
        $token = $this->appTokenFor($this->mr($this->region));

        $this->actingAsApp($token)->getJson('/api/v1/call-logs')->assertStatus(403);
        $this->actingAsApp($token)->postJson('/api/v1/call-logs', [])->assertStatus(403);
        $this->actingAsApp($token)->getJson('/api/v1/call-logs/follow-ups')->assertStatus(403);
    }

    public function test_the_status_enum_names_the_outcome_the_dashboard_counts(): void
    {
        $this->assertTrue(CallStatus::Converted->isConversion());
        $this->assertFalse(CallStatus::Interested->isConversion());
        $this->assertTrue(CallStatus::Callback->needsFollowUp());
        $this->assertFalse(CallStatus::Converted->needsFollowUp());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
