<?php

namespace Tests\Feature\ClientTypes;

use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * The client-type master. What a client may be is data now, not a PHP enum,
 * so the rules that used to be the compiler's are tested here instead: the
 * slug a client row stores cannot move, cannot collide, and cannot vanish
 * from under the clients that carry it.
 */
class ClientTypePanelTest extends TestCase
{
    public function test_the_three_built_in_types_arrive_with_the_migration(): void
    {
        $this->assertSame(
            ['doctor', 'hospital', 'medical'],
            ClientType::query()->orderBy('sort_order')->pluck('slug')->all(),
        );
    }

    public function test_an_admin_lists_the_types(): void
    {
        ClientType::factory()->count(2)->create();

        $this->actingAs($this->admin())->get('/client-types')
            ->assertOk()
            ->assertViewHas('types', fn (LengthAwarePaginator $p) => $p->total() === 5);
    }

    public function test_an_admin_adds_a_type_and_the_code_is_derived_from_the_label(): void
    {
        $this->actingAs($this->admin())->post('/client-types', [
            'label' => 'Chemist Shop',
            'colour' => 'warning',
            'is_active' => '1',
        ])->assertRedirect(route('client-types.index'));

        $this->assertDatabaseHas('client_types', [
            'slug' => 'chemist-shop',
            'label' => 'Chemist Shop',
            'colour' => 'warning',
            'is_active' => true,
        ]);
    }

    public function test_a_duplicate_code_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->from('/client-types/create')
            ->post('/client-types', ['label' => 'Doctor', 'colour' => 'primary'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, ClientType::where('slug', 'doctor')->count());
    }

    public function test_an_invented_colour_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->from('/client-types/create')
            ->post('/client-types', ['label' => 'Stockist', 'colour' => 'chartreuse'])
            ->assertSessionHasErrors('colour');
    }

    /** Clients store the slug, so an edit may change everything but that. */
    public function test_editing_a_type_cannot_move_its_code(): void
    {
        $type = ClientType::factory()->create(['slug' => 'stockist', 'label' => 'Stockist']);

        $this->actingAs($this->admin())->put("/client-types/{$type->id}", [
            'label' => 'Super Stockist',
            'colour' => 'info',
            'slug' => 'something-else',
            'is_active' => '1',
        ])->assertRedirect(route('client-types.index'));

        $this->assertDatabaseHas('client_types', [
            'id' => $type->id,
            'slug' => 'stockist',
            'label' => 'Super Stockist',
        ]);
    }

    public function test_a_type_still_carried_by_a_client_cannot_be_deleted(): void
    {
        $type = ClientType::factory()->create(['slug' => 'stockist']);
        Person::factory()->ofType('stockist')->create();

        $this->actingAs($this->admin())->delete("/client-types/{$type->id}")
            ->assertRedirect(route('client-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('client_types', ['id' => $type->id]);
    }

    public function test_a_built_in_type_cannot_be_deleted_even_when_unused(): void
    {
        $doctor = ClientType::where('slug', 'doctor')->firstOrFail();

        $this->actingAs($this->admin())->delete("/client-types/{$doctor->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('client_types', ['slug' => 'doctor']);
    }

    public function test_an_unused_custom_type_is_deleted(): void
    {
        $type = ClientType::factory()->create(['slug' => 'stockist']);

        $this->actingAs($this->admin())->delete("/client-types/{$type->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('client_types', ['id' => $type->id]);
    }

    /**
     * Deactivating is the way to retire a type: the clients that have it keep
     * it, and the client form stops offering it.
     */
    public function test_a_deactivated_type_leaves_the_client_form_but_not_its_clients(): void
    {
        $type = ClientType::factory()->create(['slug' => 'stockist', 'label' => 'Stockist']);
        $client = Person::factory()->ofType('stockist')->create();

        $this->actingAs($this->admin())->patch("/client-types/{$type->id}/toggle")
            ->assertSessionHas('success');

        $this->actingAs($this->admin())->get('/clients/create')
            ->assertOk()
            ->assertViewHas('types', fn ($types) => ! $types->contains('slug', 'stockist'));

        // …but editing that client still offers the type it already has, so
        // saving the form does not silently re-type them.
        $this->actingAs($this->admin())->get("/clients/{$client->id}/edit")
            ->assertOk()
            ->assertViewHas('types', fn ($types) => $types->contains('slug', 'stockist'));
    }

    /** A new type is immediately postable on a client — no deploy needed. */
    public function test_a_client_can_be_saved_with_a_type_added_in_the_panel(): void
    {
        ClientType::factory()->create(['slug' => 'stockist', 'label' => 'Stockist']);

        $this->actingAs($this->admin())->post('/clients', [
            'name' => 'Metro Stockist',
            'type' => 'stockist',
            'mobile' => '9876500011',
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('persons', ['name' => 'Metro Stockist', 'type' => 'stockist']);
    }

    public function test_a_type_that_does_not_exist_is_refused_on_a_client(): void
    {
        $this->actingAs($this->admin())
            ->from('/clients/create')
            ->post('/clients', ['name' => 'Nobody', 'type' => 'ghost', 'mobile' => '9876500012'])
            ->assertSessionHasErrors('type');
    }

    /** Specialization follows the type's flag, not a hard-coded slug. */
    public function test_specialization_is_kept_only_for_a_type_flagged_for_it(): void
    {
        ClientType::factory()->create(['slug' => 'consultant', 'has_specialization' => true]);
        ClientType::factory()->create(['slug' => 'stockist', 'has_specialization' => false]);

        $this->actingAs($this->admin())->post('/clients', [
            'name' => 'Dr Consultant',
            'type' => 'consultant',
            'mobile' => '9876500013',
            'specialization' => 'Cardiology',
        ]);

        $this->actingAs($this->admin())->post('/clients', [
            'name' => 'Metro Stockist',
            'type' => 'stockist',
            'mobile' => '9876500014',
            'specialization' => 'Cardiology',
        ]);

        $this->assertDatabaseHas('persons', ['name' => 'Dr Consultant', 'specialization' => 'Cardiology']);
        $this->assertDatabaseHas('persons', ['name' => 'Metro Stockist', 'specialization' => null]);
    }

    /** The master is admin housekeeping; it is not grantable to a telecaller. */
    public function test_a_telecaller_cannot_reach_the_master(): void
    {
        $telecaller = $this->telecaller();

        $this->actingAs($telecaller)->get('/client-types')->assertForbidden();
        $this->actingAs($telecaller)->get('/client-types/create')->assertForbidden();
    }

    public function test_the_label_and_badge_reach_the_client_list(): void
    {
        ClientType::factory()->create([
            'slug' => 'stockist',
            'label' => 'Super Stockist',
            'colour' => 'warning',
        ]);
        Person::factory()->ofType('stockist')->create(['name' => 'Metro Stockist']);

        $this->actingAs($this->admin())->get('/clients')
            ->assertOk()
            ->assertSee('Super Stockist')
            ->assertSee('badge-light-warning', false);
    }

    /** The mobile API publishes the slug and the master's label beside it. */
    public function test_the_api_carries_the_slug_and_its_label(): void
    {
        ClientType::factory()->create(['slug' => 'stockist', 'label' => 'Super Stockist']);
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        Person::factory()->ofType('stockist')->inRegion($region)->assignedToMr($mr)->create();

        $row = $this->actingAsApp($mr)->getJson('/api/v1/persons')->assertOk()->json('data.0');

        $this->assertSame('stockist', $row['type']);
        $this->assertSame('Super Stockist', $row['type_label']);
    }
}
