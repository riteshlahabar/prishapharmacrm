<?php

namespace Tests\Feature\Persons;

use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use Tests\TestCase;

/**
 * `PATCH /api/v1/persons/{person}` — Edit Client, from either app.
 *
 * The shape of the feature: a rep corrects a client they own. So the clamp
 * is **ownership**, the same one `?mine=1` uses, because the list with the
 * pencil on it is that list — every row it shows has to be a row this
 * endpoint will save, and nothing else may be touched at all.
 *
 * What the body may not carry is what `AddClientApiTest` covers for the
 * store, plus one more: the caller's own assignment. Moving a client to
 * another rep would make it vanish from the list it was edited from, so it
 * stays panel work.
 */
class EditClientApiTest extends TestCase
{
    public function test_an_mr_corrects_their_own_client(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $client = Person::factory()->inRegion($region)->assignedToMr($mr)->create([
            'name' => 'Dr A Kulkani',
            'city' => 'Pune',
        ]);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", [
                'name' => 'Dr Asha Kulkarni',
                'address' => '12 Clinic Road',
                'city' => 'Pune',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Dr Asha Kulkarni')
            ->assertJsonPath('data.address', '12 Clinic Road')
            // The relations come back loaded, so the app can drop the row
            // straight back into My Clients without a second request.
            ->assertJsonPath('data.assigned_mr.id', $mr->id);

        $this->assertDatabaseHas('persons', [
            'id' => $client->id,
            'name' => 'Dr Asha Kulkarni',
            'address' => '12 Clinic Road',
        ]);
    }

    public function test_a_telecaller_corrects_their_own_client(): void
    {
        $telecaller = $this->telecaller();
        $client = Person::factory()->assignedToTelecaller($telecaller)->create();

        $this->actingAsApp($telecaller)
            ->patchJson("/api/v1/persons/{$client->id}", ['city' => 'Nashik'])
            ->assertOk()
            ->assertJsonPath('data.city', 'Nashik');
    }

    /** A PATCH: what the body leaves out is left alone, not wiped. */
    public function test_fields_the_body_omits_are_untouched(): void
    {
        $mr = $this->mr();
        $client = Person::factory()->assignedToMr($mr)->create([
            'name' => 'Dr Meera Joshi',
            'email' => 'meera@example.com',
            'city' => 'Pune',
        ]);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", ['city' => 'Nagpur'])
            ->assertOk();

        $this->assertDatabaseHas('persons', [
            'id' => $client->id,
            'name' => 'Dr Meera Joshi',
            'email' => 'meera@example.com',
            'city' => 'Nagpur',
        ]);
    }

    /** An optional field sent empty is a field the rep cleared. */
    public function test_an_optional_field_sent_empty_is_cleared(): void
    {
        $mr = $this->mr();
        $client = Person::factory()->assignedToMr($mr)->create([
            'email' => 'meera@example.com',
            'address' => '12 Clinic Road',
        ]);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", [
                'email' => null,
                'address' => '',
            ])
            ->assertOk();

        $this->assertDatabaseHas('persons', [
            'id' => $client->id,
            'email' => null,
            'address' => null,
        ]);
    }

    /** Somebody else's client is missing, not forbidden. */
    public function test_another_reps_client_is_a_404(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        // Same region, so it is only ownership keeping this rep out.
        $theirs = Person::factory()->inRegion($region)
            ->assignedToMr($this->mr($region))
            ->create();

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$theirs->id}", ['city' => 'Pune'])
            ->assertNotFound();

        // An unowned client is nobody's to correct from an app either.
        $nobodys = Person::factory()->inRegion($region)->create();

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$nobodys->id}", ['city' => 'Pune'])
            ->assertNotFound();
    }

    /**
     * The counterpart is the one assignment an app may move: an MR hands the
     * client to a different telecaller, and the telecaller app does the
     * reverse.
     */
    public function test_the_counterpart_may_be_changed_and_unassigned(): void
    {
        $mr = $this->mr();
        $first = $this->telecaller();
        $second = $this->telecaller();
        $client = Person::factory()->assignedToMr($mr)
            ->assignedToTelecaller($first)
            ->create();

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", [
                'assigned_telecaller_id' => $second->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.assigned_telecaller.id', $second->id);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", [
                'assigned_telecaller_id' => null,
            ])
            ->assertOk();

        $this->assertDatabaseHas('persons', [
            'id' => $client->id,
            'assigned_telecaller_id' => null,
        ]);
    }

    /** Your own side is not the app's to move — that would hide the client. */
    public function test_neither_app_may_move_its_own_assignment(): void
    {
        $mr = $this->mr();
        $telecaller = $this->telecaller();

        $mrsClient = Person::factory()->assignedToMr($mr)->create();
        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$mrsClient->id}", [
                'assigned_mr_id' => $this->mr()->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_mr_id');

        $theirClient = Person::factory()->assignedToTelecaller($telecaller)->create();
        $this->actingAsApp($telecaller)
            ->patchJson("/api/v1/persons/{$theirClient->id}", [
                'assigned_telecaller_id' => $this->telecaller()->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_telecaller_id');
    }

    /** A device must not be able to re-file a client or switch it off. */
    public function test_region_and_active_state_are_refused_from_the_body(): void
    {
        $mr = $this->mr(Region::factory()->create());
        $client = Person::factory()->assignedToMr($mr)->create();

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", [
                'region_id' => Region::factory()->create()->id,
                'is_active' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['region_id', 'is_active']);
    }

    /**
     * The region stays where it was filed. It follows the assigned MR, and
     * an app cannot change that side — so there is nothing an edit could
     * derive it from, and re-deriving it would be the only way to move it.
     */
    public function test_the_region_does_not_move_on_an_edit(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $client = Person::factory()->inRegion($region)->assignedToMr($mr)->create();

        // The rep's own territory changes after the client was filed.
        $mr->forceFill(['region_id' => Region::factory()->create()->id])->save();

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", ['city' => 'Pune'])
            ->assertOk();

        $this->assertDatabaseHas('persons', [
            'id' => $client->id,
            'region_id' => $region->id,
        ]);
    }

    /** The row being edited is not its own duplicate. */
    public function test_saving_the_same_mobile_is_not_a_duplicate(): void
    {
        $mr = $this->mr();
        $client = Person::factory()->assignedToMr($mr)->create(['mobile' => '9876543210']);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", [
                'name' => 'Dr Asha Kulkarni',
                'mobile' => '9876543210',
            ])
            ->assertOk();
    }

    public function test_a_mobile_belonging_to_another_client_is_refused(): void
    {
        $mr = $this->mr();
        $client = Person::factory()->assignedToMr($mr)->create(['mobile' => '9876543210']);
        Person::factory()->create(['mobile' => '9000000001']);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", ['mobile' => '9000000001'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mobile');
    }

    /**
     * Changing a doctor into a medical store takes the specialization with
     * it, the same rule the panel and the store endpoint follow — otherwise
     * a store keeps "Cardiology" on a hidden field forever.
     */
    public function test_a_type_that_carries_no_specialization_clears_it(): void
    {
        $mr = $this->mr();
        // The kinds are a master an admin edits, so the test states the two
        // it needs rather than leaning on whatever the install ships with.
        $doctor = ClientType::factory()->create(['has_specialization' => true]);
        $store = ClientType::factory()->create(['has_specialization' => false]);

        $client = Person::factory()->assignedToMr($mr)->create([
            'type' => $doctor->slug,
            'specialization' => 'Cardiology',
        ]);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", ['type' => $store->slug])
            ->assertOk()
            ->assertJsonPath('data.specialization', null);

        $this->assertDatabaseHas('persons', [
            'id' => $client->id,
            'type' => $store->slug,
            'specialization' => null,
        ]);
    }

    /** And a type that does carry one keeps the text through an edit. */
    public function test_a_type_that_carries_a_specialization_keeps_it(): void
    {
        $mr = $this->mr();
        $doctor = ClientType::factory()->create(['has_specialization' => true]);
        $client = Person::factory()->assignedToMr($mr)->create([
            'type' => $doctor->slug,
            'specialization' => 'Cardiology',
        ]);

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", [
                'specialization' => 'Paediatrics',
            ])
            ->assertOk()
            ->assertJsonPath('data.specialization', 'Paediatrics');
    }

    /** The type master is the vocabulary; a retired type is not saveable. */
    public function test_a_retired_type_is_refused(): void
    {
        $mr = $this->mr();
        $client = Person::factory()->assignedToMr($mr)->create();
        $retired = ClientType::factory()->inactive()->create();

        $this->actingAsApp($mr)
            ->patchJson("/api/v1/persons/{$client->id}", ['type' => $retired->slug])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_a_token_from_neither_app_is_refused(): void
    {
        $mr = $this->mr();
        $client = Person::factory()->assignedToMr($mr)->create();

        $this->asGuestApp()
            ->patchJson("/api/v1/persons/{$client->id}", ['city' => 'Pune'])
            ->assertUnauthorized();
    }
}
