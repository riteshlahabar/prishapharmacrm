<?php

namespace Tests\Feature\Visits;

use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Models\Visit;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * The panel's visit report. Visits are filed by the MR app, so the panel reads
 * and (with the permission) deletes, but never creates.
 *
 * A visit is scoped by **territory**: anyone covering the person's region may
 * read it (docs/authorization.md).
 */
class VisitPanelTest extends TestCase
{
    public function test_an_admin_sees_every_visit(): void
    {
        $a = Region::factory()->create();
        $b = Region::factory()->create();

        Visit::factory()->forPerson(Person::factory()->inRegion($a)->create())->create();
        Visit::factory()->forPerson(Person::factory()->inRegion($b)->create())->create();

        $this->actingAs($this->admin())->get('/visits')
            ->assertOk()
            ->assertViewHas('visits', fn (LengthAwarePaginator $p) => $p->total() === 2);
    }

    public function test_an_admin_opens_a_visit(): void
    {
        $visit = Visit::factory()->forPerson(Person::factory()->create(['name' => 'Dr Rao']))->create();

        $this->actingAs($this->admin())->get("/visits/{$visit->id}")
            ->assertOk()
            ->assertSee('Dr Rao');
    }

    public function test_a_telecaller_granted_the_permission_sees_only_their_territory(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();

        $telecaller = $this->telecaller([$mine]);
        $this->grant($telecaller, 'visits.view');

        $visible = Visit::factory()->forPerson(Person::factory()->inRegion($mine)->create())->create();
        Visit::factory()->forPerson(Person::factory()->inRegion($theirs)->create())->create();

        $this->actingAs($telecaller)->get('/visits')
            ->assertOk()
            ->assertViewHas('visits', fn (LengthAwarePaginator $p) => $p->pluck('id')->all() === [$visible->id]);
    }

    /** Outside your territory reads as missing, not forbidden. */
    public function test_a_visit_outside_the_territory_cannot_be_opened_by_id(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->grant($telecaller, 'visits.view');

        $hidden = Visit::factory()
            ->forPerson(Person::factory()->inRegion(Region::factory()->create())->create())
            ->create();

        $this->actingAs($telecaller)->get("/visits/{$hidden->id}")->assertNotFound();
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/visits')->assertForbidden();
    }

    public function test_a_telecaller_without_the_permission_is_refused(): void
    {
        $this->actingAs($this->telecaller([Region::factory()->create()]))
            ->get('/visits')
            ->assertForbidden();
    }

    public function test_deleting_needs_its_own_permission(): void
    {
        $visit = Visit::factory()->forPerson(Person::factory()->create())->create();
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->grant($telecaller, 'visits.view');

        $this->actingAs($telecaller)->delete("/visits/{$visit->id}")->assertForbidden();

        $this->assertDatabaseHas('visits', ['id' => $visit->id]);
    }

    public function test_an_admin_deletes_a_visit(): void
    {
        $visit = Visit::factory()->forPerson(Person::factory()->create())->create();

        $this->actingAs($this->admin())
            ->delete("/visits/{$visit->id}")
            ->assertRedirect(route('visits.index'));

        $this->assertDatabaseMissing('visits', ['id' => $visit->id]);
    }

    /** You cannot delete what you cannot see: resolved through the scope first. */
    public function test_a_scoped_viewer_cannot_delete_outside_their_territory(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->grant($telecaller, 'visits.view', 'visits.delete');

        $hidden = Visit::factory()
            ->forPerson(Person::factory()->inRegion(Region::factory()->create())->create())
            ->create();

        $this->actingAs($telecaller)->delete("/visits/{$hidden->id}")->assertNotFound();

        $this->assertDatabaseHas('visits', ['id' => $hidden->id]);
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/visits')->assertRedirect(route('login'));
    }
}
