<?php

namespace Tests\Feature\Visits;

use App\Modules\Persons\Models\Person;
use App\Modules\Products\Models\Product;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Enums\VisitPurpose;
use App\Modules\Visits\Models\Visit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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

    public function test_an_mr_files_a_visit_with_a_shopfront_photo(): void
    {
        // Both ends of the `before_or_equal:now` check are pinned in UTC: the
        // app sends `photo_taken_at` as `toUtc().toIso8601String()`, so a
        // naive "now" here would read as IST and put the shutter 5h30 in the
        // future.
        Carbon::setTestNow('2026-11-24T11:00:00Z');
        Storage::fake('local');

        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $person = Person::factory()->inRegion($region)->create();

        $this->actingAsApp($mr)->post('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'product_detailing',
            'photo' => UploadedFile::fake()->image('shopfront.jpg', 1200, 900),
            'photo_taken_at' => '2026-11-24T10:55:00Z',
        ], ['Accept' => 'application/json'])
            ->assertStatus(201)
            // Reported, never linked: the file has no URL an app could fetch.
            ->assertJsonPath('data.has_photo', true)
            ->assertJsonMissingPath('data.photo_path');

        $visit = Visit::firstOrFail();
        $this->assertTrue($visit->hasPhoto());
        // The instant the phone reported, not a local rendering of it.
        $this->assertTrue($visit->photo_taken_at->eq(Carbon::parse('2026-11-24T10:55:00Z')));
        // Private disk, dated folder, and a name of ours — not the phone's.
        $this->assertStringStartsWith('visit-photos/2026/11/', $visit->photo_path);
        Storage::disk('local')->assertExists($visit->photo_path);
    }

    /** The common case: no camera, no photo, and the visit still files. */
    public function test_a_visit_without_a_photo_is_still_accepted(): void
    {
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.has_photo', false)
            ->assertJsonPath('data.photo_taken_at', null);

        $visit = Visit::firstOrFail();
        $this->assertFalse($visit->hasPhoto());
        // No photo, so nothing dates one.
        $this->assertNull($visit->photo_taken_at);
    }

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        Storage::fake('local');
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->post('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
            'photo' => UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonStructure(['errors' => ['photo']]);

        // Nothing filed and nothing written: the upload is checked before the
        // row, so a rejected photo never leaves a visit or a file behind.
        $this->assertDatabaseCount('visits', 0);
    }

    public function test_an_oversized_photo_is_rejected(): void
    {
        Storage::fake('local');
        $mr = $this->mr();
        $person = Person::factory()->create();

        $this->actingAsApp($mr)->post('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'courtesy',
            // 9 MB, past the 8 MB ceiling the request sets.
            'photo' => UploadedFile::fake()->create('huge.jpg', 9216, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['photo']]);
    }

    /** Multipart posts strings; the rest of the payload must survive it. */
    public function test_product_lines_survive_a_multipart_post(): void
    {
        Storage::fake('local');
        $mr = $this->mr();
        $person = Person::factory()->create();
        $product = Product::factory()->create(['cost' => '120.50']);

        $this->actingAsApp($mr)->post('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'product_detailing',
            'in_time' => '10:30',
            'out_time' => '11:15',
            'latitude' => '19.0760000',
            'longitude' => '72.8777000',
            'photo' => UploadedFile::fake()->image('shopfront.jpg'),
            'products' => [['product_id' => $product->id, 'box_qty' => 3]],
        ], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('data.products.0.box_qty', 3)
            ->assertJsonPath('data.products.0.unit_cost', '120.50');

        $visit = Visit::firstOrFail();
        $this->assertSame(45, $visit->durationMinutes());
        $this->assertTrue($visit->hasLocation());
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
