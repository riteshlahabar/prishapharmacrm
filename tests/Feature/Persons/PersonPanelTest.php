<?php

namespace Tests\Feature\Persons;

use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * Admin CRUD for the contact book, plus the telecaller's scoped read.
 *
 * A telecaller holds `persons.view` by default (PermissionCatalog), so the
 * clamp on this module matters more than on any other panel screen.
 */
class PersonPanelTest extends TestCase
{
    public function test_an_admin_lists_every_person(): void
    {
        Person::factory()->count(3)->inRegion(Region::factory()->create())->create();

        $this->actingAs($this->admin())->get('/clients')
            ->assertOk()
            ->assertViewHas('clients', fn (LengthAwarePaginator $p) => $p->total() === 3);
    }

    public function test_an_admin_creates_a_person(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $telecaller = $this->telecaller([$region]);

        $this->actingAs($this->admin())->post('/clients', [
            'name' => 'Dr Anjali Rao',
            'type' => 'doctor',
            'mobile' => '9876543210',
            'email' => 'Anjali@Example.COM',
            'city' => 'Pune',
            'specialization' => 'Cardiology',
            'region_id' => $region->id,
            'assigned_mr_id' => $mr->id,
            'assigned_telecaller_id' => $telecaller->id,
            'is_active' => '1',
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('persons', [
            'name' => 'Dr Anjali Rao',
            'type' => 'doctor',
            // The payload lower-cases the address.
            'email' => 'anjali@example.com',
            'region_id' => $region->id,
            'assigned_mr_id' => $mr->id,
        ]);
    }

    public function test_the_mobile_number_must_be_unique(): void
    {
        Person::factory()->create(['mobile' => '9876543210']);

        $this->actingAs($this->admin())
            ->from('/clients/create')
            ->post('/clients', ['name' => 'Someone', 'type' => 'doctor', 'mobile' => '9876543210'])
            ->assertSessionHasErrors('mobile');
    }

    public function test_the_mobile_number_is_format_checked(): void
    {
        $this->actingAs($this->admin())
            ->from('/clients/create')
            ->post('/clients', ['name' => 'Someone', 'type' => 'doctor', 'mobile' => 'not a number'])
            ->assertSessionHasErrors('mobile');
    }

    /** A telecaller id posted into the MR field must not be accepted. */
    public function test_an_assignee_must_actually_hold_the_role(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);

        $this->actingAs($this->admin())
            ->from('/clients/create')
            ->post('/clients', [
                'name' => 'Someone',
                'type' => 'doctor',
                'mobile' => '9876500000',
                'assigned_mr_id' => $telecaller->id,
            ])
            ->assertSessionHasErrors('assigned_mr_id');
    }

    public function test_an_admin_updates_a_person(): void
    {
        $person = Person::factory()->create(['name' => 'Old Name', 'mobile' => '9800000001']);

        $this->actingAs($this->admin())->put("/clients/{$person->id}", [
            'name' => 'New Name',
            'type' => 'hospital',
            'mobile' => '9800000001',
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('persons', ['id' => $person->id, 'name' => 'New Name', 'type' => 'hospital']);
    }

    public function test_an_admin_toggles_a_person_and_deletes_one(): void
    {
        $person = Person::factory()->create();

        $this->actingAs($this->admin())->patch("/clients/{$person->id}/toggle")
            ->assertRedirect(route('clients.index'));
        $this->assertFalse($person->refresh()->is_active);

        $this->actingAs($this->admin())->delete("/clients/{$person->id}")
            ->assertRedirect(route('clients.index'));
        $this->assertDatabaseMissing('persons', ['id' => $person->id]);
    }

    public function test_a_telecaller_lists_only_persons_in_their_regions(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();
        $telecaller = $this->telecaller([$mine]);

        $visible = Person::factory()->inRegion($mine)->create();
        Person::factory()->count(4)->inRegion($theirs)->create();

        $this->actingAs($telecaller)->get('/clients')
            ->assertOk()
            ->assertViewHas('clients', fn (LengthAwarePaginator $p) => $p->pluck('id')->all() === [$visible->id]);
    }

    /**
     * The list was already clamped; the detail page is reached by id, so it
     * needs the same clamp or the scope is only cosmetic. PersonPolicy::view
     * holds the rule, and a denial reads as missing.
     */
    public function test_a_telecaller_cannot_open_a_person_outside_their_regions(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $hidden = Person::factory()->inRegion(Region::factory()->create())->create();

        $this->actingAs($telecaller)->get("/clients/{$hidden->id}")->assertNotFound();
    }

    public function test_a_telecaller_can_open_a_person_in_their_own_region(): void
    {
        $mine = Region::factory()->create();
        $telecaller = $this->telecaller([$mine]);
        $person = Person::factory()->inRegion($mine)->create(['name' => 'Dr Visible']);

        $this->actingAs($telecaller)->get("/clients/{$person->id}")
            ->assertOk()
            ->assertSee('Dr Visible');
    }

    public function test_a_person_with_no_region_is_admin_only(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $unassigned = Person::factory()->create(['region_id' => null]);

        $this->actingAs($telecaller)->get("/clients/{$unassigned->id}")->assertNotFound();
        $this->actingAs($this->admin())->get("/clients/{$unassigned->id}")->assertOk();
    }

    public function test_a_telecaller_cannot_reach_the_edit_form_outside_their_regions(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->grant($telecaller, 'persons.update');

        $hidden = Person::factory()->inRegion(Region::factory()->create())->create();

        $this->actingAs($telecaller)->get("/clients/{$hidden->id}/edit")->assertNotFound();
    }

    public function test_a_telecaller_cannot_create_or_delete_by_default(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $person = Person::factory()->create();

        $this->actingAs($telecaller)->get('/clients/create')->assertForbidden();
        $this->actingAs($telecaller)->post('/clients', [])->assertForbidden();
        $this->actingAs($telecaller)->delete("/clients/{$person->id}")->assertForbidden();
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/clients')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/clients')->assertRedirect(route('login'));
    }
}
