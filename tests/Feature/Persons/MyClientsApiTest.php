<?php

namespace Tests\Feature\Persons;

use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use Tests\TestCase;

/**
 * `GET /api/v1/persons?mine=1` — the My Clients screen in both apps.
 *
 * The clamp is **ownership**, not territory: the rows this caller is an
 * assignee on, wherever they sit. That is the half of the feature that makes
 * cross-assignment visible — a client a telecaller adds and hands to an MR
 * has to turn up in that MR's list, and vice versa.
 *
 * The unflagged call keeps the territory scope `PersonApiTest` covers; these
 * tests are about how the two differ.
 */
class MyClientsApiTest extends TestCase
{
    public function test_an_mr_sees_the_clients_assigned_to_them(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);

        Person::factory()->count(2)->inRegion($region)->assignedToMr($mr)->create();
        // Same region, another rep's.
        Person::factory()->count(3)->inRegion($region)->assignedToMr($this->mr($region))->create();
        // Same region, nobody's.
        Person::factory()->inRegion($region)->create();

        $this->actingAsApp($mr)->getJson('/api/v1/persons?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_a_telecaller_sees_the_clients_assigned_to_them_not_their_whole_region(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);

        Person::factory()->count(2)->inRegion($region)->assignedToTelecaller($telecaller)->create();
        // In their territory, but somebody else's client — on the queue, not
        // in My Clients. This is the pair of lists not agreeing, on purpose.
        Person::factory()->count(4)->inRegion($region)->create();

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('meta.total', 6);
    }

    /**
     * The point of the feature: a client added in one app reaches the
     * counterpart named on it, through the other app's My Clients.
     */
    public function test_a_client_added_by_a_telecaller_reaches_the_mr_it_was_assigned_to(): void
    {
        $mrRegion = Region::factory()->create();
        $mr = $this->mr($mrRegion);
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAsApp($telecaller)
            ->postJson('/api/v1/persons', [
                'name' => 'Dr Nandini Rao',
                'type' => 'doctor',
                'mobile' => '9811122233',
                'specialization' => 'ENT',
                'assigned_mr_id' => $mr->id,
            ])
            ->assertCreated();

        $this->actingAsApp($mr)->getJson('/api/v1/persons?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Dr Nandini Rao')
            // Each side can see who it is sharing the client with.
            ->assertJsonPath('data.0.assigned_telecaller.id', $telecaller->id);
    }

    public function test_a_client_added_by_an_mr_reaches_the_telecaller_it_was_assigned_to(): void
    {
        $mr = $this->mr(Region::factory()->create());
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', [
                'name' => 'Sunrise Medicals',
                'type' => 'medical',
                'mobile' => '9822233344',
                'assigned_telecaller_id' => $telecaller->id,
            ])
            ->assertCreated();

        $this->actingAsApp($telecaller)->getJson('/api/v1/persons?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Sunrise Medicals')
            ->assertJsonPath('data.0.assigned_mr.id', $mr->id);
    }

    /**
     * Ownership outranks territory here. The MR is assigned a client sitting
     * in a region they do not cover — an admin may do that, and the panel's
     * own lists hide it — but it is still their responsibility, so My Clients
     * shows it where the queue does not.
     */
    public function test_ownership_is_not_narrowed_by_territory(): void
    {
        $elsewhere = Region::factory()->create();
        $mr = $this->mr(Region::factory()->create());

        Person::factory()->inRegion($elsewhere)->assignedToMr($mr)->create();

        $this->actingAsApp($mr)->getJson('/api/v1/persons?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        // The territory-scoped call still refuses it, unchanged.
        $this->actingAsApp($mr)->getJson('/api/v1/persons')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    /** My Clients is a live list, like every other: no switched-off rows. */
    public function test_deactivated_clients_are_left_out(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);

        Person::factory()->inRegion($region)->assignedToMr($mr)->create();
        Person::factory()->inRegion($region)->assignedToMr($mr)->inactive()->create();

        $this->actingAsApp($mr)->getJson('/api/v1/persons?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_search_box_still_narrows_my_clients(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);

        Person::factory()->inRegion($region)->assignedToMr($mr)->create(['name' => 'Dr Harish Pai']);
        Person::factory()->inRegion($region)->assignedToMr($mr)->create(['name' => 'City Hospital']);

        $this->actingAsApp($mr)->getJson('/api/v1/persons?mine=1&search=Harish')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Dr Harish Pai');
    }
}
