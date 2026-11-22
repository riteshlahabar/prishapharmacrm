<?php

namespace Tests\Feature\Persons;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Persons\Enums\PersonType;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use Tests\TestCase;

/**
 * `/api/v1/persons` — the one endpoint both apps share.
 *
 * Scoping is by role and happens server-side: a telecaller sees the persons in
 * the regions they cover, an MR sees the ones assigned to them. The app never
 * sends a region filter it could tamper with.
 */
class PersonApiTest extends TestCase
{
    public function test_an_mr_sees_only_their_own_assignments(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);

        Person::factory()->count(2)->inRegion($region)->assignedToMr($mr)->create();
        // Same region, someone else's doctor.
        Person::factory()->count(3)->inRegion($region)->assignedToMr($this->mr($region))->create();
        // Same region, nobody's.
        Person::factory()->inRegion($region)->create();

        $this->actingAsApp($mr)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_a_telecaller_sees_every_person_in_the_regions_they_cover(): void
    {
        $mine = Region::factory()->create();
        $alsoMine = Region::factory()->create();
        $theirs = Region::factory()->create();

        $telecaller = $this->telecaller([$mine, $alsoMine]);

        Person::factory()->count(2)->inRegion($mine)->create();
        Person::factory()->inRegion($alsoMine)->create();
        Person::factory()->count(4)->inRegion($theirs)->create();

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    /** An empty territory means no rows, never every row. */
    public function test_a_telecaller_with_no_region_sees_nobody(): void
    {
        Person::factory()->count(3)->inRegion(Region::factory()->create())->create();

        $this->actingAsApp($this->telecaller())->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    /** The apps work a live list; a deactivated person is not today's work. */
    public function test_deactivated_persons_are_left_out_for_both_apps(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $telecaller = $this->telecaller([$region]);

        Person::factory()->inRegion($region)->assignedToMr($mr)->create();
        Person::factory()->inRegion($region)->assignedToMr($mr)->inactive()->create();

        $this->actingAsApp($mr)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_a_tampered_region_filter_cannot_widen_the_scope(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();
        $telecaller = $this->telecaller([$mine]);

        Person::factory()->inRegion($mine)->create();
        Person::factory()->count(5)->inRegion($theirs)->create();

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons?region_id='.$theirs->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_the_list_can_be_searched_and_filtered_by_type(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);

        Person::factory()->inRegion($region)->create(['name' => 'Dr Anjali Rao']);
        Person::factory()->inRegion($region)->hospital()->create(['name' => 'Sunrise Hospital']);
        Person::factory()->inRegion($region)->medical()->create(['name' => 'Patel Medicals']);

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons?search=Anjali')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Dr Anjali Rao');

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons?type=hospital')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'hospital');
    }

    /**
     * For a telecaller the list doubles as the call queue, so each row carries
     * the last call *they* made — one query for the whole page, through the
     * Calls module's service contract.
     */
    public function test_a_telecaller_row_carries_their_own_last_call(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);
        $person = Person::factory()->inRegion($region)->create();

        CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->calledDaysAgo(3)->create();
        $latest = CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->converted()->create();

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('data.0.last_call.id', $latest->id)
            ->assertJsonPath('data.0.last_call.status', 'converted');
    }

    public function test_another_telecallers_call_is_not_shown_as_yours(): void
    {
        $region = Region::factory()->create();
        $mine = $this->telecaller([$region]);
        $theirs = $this->telecaller([$region]);
        $person = Person::factory()->inRegion($region)->create();

        CallLog::factory()->forTelecaller($theirs)->forPerson($person)->create();

        $this->actingAsApp($mine)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('data.0.last_call', null);
    }

    public function test_a_person_never_called_has_a_null_last_call(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);
        Person::factory()->inRegion($region)->create();

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('data.0.last_call', null);
    }

    /** `last_call` is the telecaller's queue column and nothing else's. */
    public function test_the_mr_list_carries_no_last_call_column(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        Person::factory()->inRegion($region)->assignedToMr($mr)->create();

        $row = $this->actingAsApp($mr)->getJson('/api/v1/persons')->assertOk()->json('data.0');

        $this->assertArrayNotHasKey('last_call', $row);
    }

    public function test_the_person_type_enum_decides_which_fields_matter(): void
    {
        $this->assertTrue(PersonType::Doctor->hasSpecialization());
        $this->assertFalse(PersonType::Hospital->hasSpecialization());
        $this->assertFalse(PersonType::Medical->hasSpecialization());
    }
}
