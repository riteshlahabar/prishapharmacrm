<?php

namespace Tests\Feature\Visits;

use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Enums\VisitPurpose;
use App\Modules\Visits\Models\Visit;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * `/api/v1/visits` — the MR app files a visit and reads its own history
 * (docs/api/v1-mr.md).
 *
 * `mr_id` is never accepted from the body: the service takes it from the
 * token, so a tampered build cannot file work under another rep's name.
 */
class VisitApiTest extends TestCase
{
    public function test_an_mr_files_a_visit(): void
    {
        Carbon::setTestNow('2026-11-22 11:00:00');
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $person = Person::factory()->inRegion($region)->create(['name' => 'Dr Mehta']);

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'product_detailing',
            'in_time' => '10:30',
            'out_time' => '11:15',
            'latitude' => 19.076,
            'longitude' => 72.8777,
            'notes' => 'Discussed the new range.',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.person.id', $person->id)
            ->assertJsonPath('data.person.name', 'Dr Mehta')
            ->assertJsonPath('data.purpose', 'product_detailing');

        $visit = Visit::firstOrFail();
        $this->assertSame($mr->id, $visit->mr_id);
        $this->assertSame(45, $visit->durationMinutes());
    }

    public function test_the_filing_rep_comes_from_the_token_not_the_body(): void
    {
        $region = Region::factory()->create();
        $mine = $this->mr($region);
        $other = $this->mr($region);
        $person = Person::factory()->inRegion($region)->create();

        $this->actingAsApp($mine)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
            // A tampered build trying to file under someone else.
            'mr_id' => $other->id,
        ])->assertStatus(201);

        $this->assertSame($mine->id, Visit::firstOrFail()->mr_id);
    }

    public function test_the_visit_date_defaults_to_today(): void
    {
        Carbon::setTestNow('2026-11-22 11:00:00');
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'sample_drop',
        ])->assertStatus(201);

        $this->assertSame('2026-11-22', Visit::firstOrFail()->visit_date->toDateString());
    }

    /** A visit is reported, not scheduled. */
    public function test_a_visit_cannot_be_dated_in_the_future(): void
    {
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
            'visit_date' => now()->addDay()->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonPath('errors.visit_date.0', 'A visit cannot be dated in the future.');
    }

    public function test_the_person_is_required(): void
    {
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', ['purpose' => 'courtesy'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['person_id']]);
    }

    public function test_an_unknown_person_is_rejected(): void
    {
        $mr = $this->mr();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => 99999,
            'purpose' => 'courtesy',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.person_id.0', 'That person is not on the active list.');
    }

    public function test_a_deactivated_person_cannot_be_visited(): void
    {
        $mr = $this->mr();
        $person = Person::factory()->inactive()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.person_id.0', 'That person is not on the active list.');
    }

    public function test_an_unknown_purpose_is_rejected(): void
    {
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'chatting',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['purpose']]);
    }

    public function test_the_out_time_cannot_precede_the_in_time(): void
    {
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
            'in_time' => '14:00',
            'out_time' => '13:00',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.out_time.0', 'The out time cannot be before the in time.');
    }

    public function test_one_coordinate_without_the_other_is_rejected(): void
    {
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
            'longitude' => 72.8777,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.latitude.0', 'Both coordinates are needed, or neither.');
    }

    public function test_a_visit_may_be_filed_without_a_gps_fix(): void
    {
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
        ])->assertStatus(201);

        $this->assertFalse(Visit::firstOrFail()->hasLocation());
    }

    public function test_the_history_shows_only_the_callers_own_visits(): void
    {
        $region = Region::factory()->create();
        $mine = $this->mr($region);
        $theirs = $this->mr($region);
        $person = Person::factory()->inRegion($region)->create();

        Visit::factory()->count(2)->forMr($mine)->forPerson($person)->create();
        Visit::factory()->count(3)->forMr($theirs)->forPerson($person)->create();

        $this->actingAsApp($mine)->getJson('/api/v1/visits')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_the_history_can_be_filtered(): void
    {
        $mr = $this->mr();
        $a = Person::factory()->create();
        $b = Person::factory()->create();

        Visit::factory()->forMr($mr)->forPerson($a)->purpose(VisitPurpose::SampleDrop)->create();
        Visit::factory()->forMr($mr)->forPerson($b)->purpose(VisitPurpose::Courtesy)->create();

        $this->actingAsApp($mr)->getJson('/api/v1/visits?person_id='.$a->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->actingAsApp($mr)->getJson('/api/v1/visits?purpose=courtesy')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_history_can_be_narrowed_to_a_date_range(): void
    {
        Carbon::setTestNow('2026-11-22 12:00:00');
        $mr = $this->mr();
        $person = Person::factory()->create();

        Visit::factory()->forMr($mr)->forPerson($person)->onDate('2026-11-01')->create();
        Visit::factory()->forMr($mr)->forPerson($person)->onDate('2026-11-21')->create();

        $this->actingAsApp($mr)->getJson('/api/v1/visits?date_from=2026-11-15&date_to=2026-11-22')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_telecaller_app_cannot_file_or_read_visits(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $token = $this->appTokenFor($telecaller);

        $this->actingAsApp($token)->getJson('/api/v1/visits')->assertStatus(403);
        $this->actingAsApp($token)->postJson('/api/v1/visits', [])->assertStatus(403);
    }

    public function test_an_unfinished_visit_reports_no_duration_rather_than_guessing(): void
    {
        $visit = Visit::factory()->unfinished()->create();

        $this->assertNull($visit->durationMinutes());
        $this->assertSame('—', $visit->durationLabel());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
