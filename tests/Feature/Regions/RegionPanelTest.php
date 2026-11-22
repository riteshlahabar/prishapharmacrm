<?php

namespace Tests\Feature\Regions;

use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * Admin CRUD for territories. Regions are the scoping key for the whole
 * telecaller side, so deleting one that is still in use is refused rather than
 * silently orphaning the people working it.
 */
class RegionPanelTest extends TestCase
{
    public function test_an_admin_lists_regions(): void
    {
        Region::factory()->count(3)->create();

        $this->actingAs($this->admin())->get('/regions')
            ->assertOk()
            ->assertViewHas('regions', fn (LengthAwarePaginator $p) => $p->total() === 3);
    }

    public function test_an_admin_creates_a_region_and_the_code_is_upper_cased(): void
    {
        $this->actingAs($this->admin())->post('/regions', [
            'name' => 'Pune West',
            'code' => 'pnw-1',
            'is_active' => '1',
        ])->assertRedirect(route('regions.index'));

        $this->assertDatabaseHas('regions', ['name' => 'Pune West', 'code' => 'PNW-1', 'is_active' => true]);
    }

    public function test_the_code_must_be_unique(): void
    {
        Region::factory()->named('Existing', 'DUP')->create();

        $this->actingAs($this->admin())
            ->from('/regions/create')
            ->post('/regions', ['name' => 'Another', 'code' => 'DUP'])
            ->assertSessionHasErrors('code');
    }

    /** Case-insensitively unique, since the code is stored upper-cased. */
    public function test_a_lower_case_duplicate_code_is_also_refused(): void
    {
        Region::factory()->named('Existing', 'DUP')->create();

        $this->actingAs($this->admin())
            ->from('/regions/create')
            ->post('/regions', ['name' => 'Another', 'code' => 'dup'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Region::where('code', 'DUP')->count());
    }

    public function test_the_code_rejects_punctuation(): void
    {
        $this->actingAs($this->admin())
            ->from('/regions/create')
            ->post('/regions', ['name' => 'Bad Code', 'code' => 'P N/W!'])
            ->assertSessionHasErrors('code');
    }

    public function test_the_name_and_code_are_required(): void
    {
        $this->actingAs($this->admin())
            ->from('/regions/create')
            ->post('/regions', [])
            ->assertSessionHasErrors(['name', 'code']);
    }

    public function test_an_admin_updates_a_region(): void
    {
        $region = Region::factory()->named('Old', 'OLD')->create();

        $this->actingAs($this->admin())->put("/regions/{$region->id}", [
            'name' => 'New Name',
            'code' => 'NEW',
            'is_active' => '1',
        ])->assertRedirect(route('regions.index'));

        $this->assertDatabaseHas('regions', ['id' => $region->id, 'name' => 'New Name', 'code' => 'NEW']);
    }

    public function test_a_region_keeps_its_own_code_on_edit(): void
    {
        $region = Region::factory()->named('Same', 'SAME')->create();

        $this->actingAs($this->admin())->put("/regions/{$region->id}", [
            'name' => 'Same',
            'code' => 'SAME',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();
    }

    public function test_an_unused_region_can_be_deleted(): void
    {
        $region = Region::factory()->create();

        $this->actingAs($this->admin())->delete("/regions/{$region->id}")
            ->assertRedirect(route('regions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('regions', ['id' => $region->id]);
    }

    /** Deleting a worked territory would orphan the people in it. */
    public function test_a_region_with_persons_in_it_cannot_be_deleted(): void
    {
        $region = Region::factory()->create();
        Person::factory()->inRegion($region)->create();

        $this->actingAs($this->admin())->delete("/regions/{$region->id}")
            ->assertRedirect(route('regions.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('regions', ['id' => $region->id]);
    }

    public function test_a_region_with_an_mr_in_it_cannot_be_deleted(): void
    {
        $region = Region::factory()->create();
        $this->mr($region);

        $this->actingAs($this->admin())->delete("/regions/{$region->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('regions', ['id' => $region->id]);
    }

    public function test_a_region_a_telecaller_covers_cannot_be_deleted(): void
    {
        $region = Region::factory()->create();
        $this->telecaller([$region]);

        $this->actingAs($this->admin())->delete("/regions/{$region->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('regions', ['id' => $region->id]);
    }

    public function test_a_region_can_be_toggled(): void
    {
        $region = Region::factory()->create();

        $this->actingAs($this->admin())->patch("/regions/{$region->id}/toggle")
            ->assertRedirect(route('regions.index'));
        $this->assertFalse($region->refresh()->is_active);

        $this->actingAs($this->admin())->patch("/regions/{$region->id}/toggle");
        $this->assertTrue($region->refresh()->is_active);
    }

    public function test_the_list_can_be_searched(): void
    {
        Region::factory()->named('Pune West', 'PNW')->create();
        Region::factory()->named('Nashik', 'NSK')->create();

        $this->actingAs($this->admin())->get('/regions?search=Pune')
            ->assertOk()
            ->assertViewHas('regions', fn (LengthAwarePaginator $p) => $p->total() === 1);
    }

    /** Region administration is not grantable to a telecaller. */
    public function test_a_telecaller_cannot_reach_region_administration(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($telecaller)->get('/regions')->assertForbidden();
        $this->actingAs($telecaller)->post('/regions', [])->assertForbidden();
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/regions')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/regions')->assertRedirect(route('login'));
    }

    public function test_the_label_reads_name_and_code(): void
    {
        $this->assertSame('Pune West (PNW)', Region::factory()->named('Pune West', 'PNW')->create()->label());
    }
}
