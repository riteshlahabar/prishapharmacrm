<?php

namespace Tests\Feature\Users;

use App\Modules\Regions\Models\Region;
use Tests\TestCase;

/**
 * `/api/v1/assignees/{mrs,telecallers}` — the two Add Client dropdowns.
 *
 * Each app asks for the *other* role, so each route is gated on the ability
 * of the app that needs it. The list is every active user of that role,
 * unscoped by territory: cross-territory assignment is the point of the
 * feature, and the field is required, so a region filter coming back empty
 * would make a client impossible to add at all.
 */
class AssigneePickerApiTest extends TestCase
{
    public function test_a_telecaller_gets_every_active_mr_with_their_region(): void
    {
        $pune = Region::factory()->create(['name' => 'Pune']);
        $mumbai = Region::factory()->create(['name' => 'Mumbai']);

        $this->mr($pune, ['name' => 'Anil Joshi']);
        $this->mr($mumbai, ['name' => 'Bhavna Shah']);
        $this->mr($pune, ['name' => 'Switched Off', 'is_active' => false]);

        $response = $this->actingAsApp($this->telecaller([$pune]))
            ->getJson('/api/v1/assignees/mrs')
            ->assertOk()
            // A small picker: a plain array, no `meta`.
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['name' => 'Switched Off']);

        // Ordered by name, and each row says where the rep works — so a
        // telecaller covering only Pune can still choose the Mumbai rep
        // knowingly.
        $response->assertJsonPath('data.0.name', 'Anil Joshi')
            ->assertJsonPath('data.0.regions.0.name', 'Pune')
            ->assertJsonPath('data.1.name', 'Bhavna Shah')
            ->assertJsonPath('data.1.regions.0.name', 'Mumbai');

        $this->assertArrayNotHasKey('meta', $response->json());
    }

    public function test_an_mr_gets_every_active_telecaller_with_all_their_regions(): void
    {
        $pune = Region::factory()->create(['name' => 'Pune']);
        $nashik = Region::factory()->create(['name' => 'Nashik']);

        $this->telecaller([$pune, $nashik], ['name' => 'Priya Sharma']);
        $this->telecaller([], ['name' => 'Switched Off', 'is_active' => false]);

        $this->actingAsApp($this->mr($pune))
            ->getJson('/api/v1/assignees/telecallers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Priya Sharma')
            // A telecaller covers several regions; one shape serves both roles.
            ->assertJsonCount(2, 'data.0.regions');
    }

    /** A picker has no business carrying email addresses or activity flags. */
    public function test_a_picker_row_carries_only_what_it_is_chosen_by(): void
    {
        $region = Region::factory()->create();
        $this->mr($region);

        $row = $this->actingAsApp($this->telecaller([$region]))
            ->getJson('/api/v1/assignees/mrs')
            ->assertOk()
            ->json('data.0');

        $this->assertSame(['id', 'name', 'mobile', 'regions'], array_keys($row));
    }

    /** Asking for your own role is a 403, not an empty list. */
    public function test_each_app_may_only_ask_for_the_other_role(): void
    {
        $region = Region::factory()->create();

        $this->actingAsApp($this->mr($region))
            ->getJson('/api/v1/assignees/mrs')
            ->assertStatus(403)
            ->assertJsonPath('errors.reason', 'forbidden');

        $this->actingAsApp($this->telecaller([$region]))
            ->getJson('/api/v1/assignees/telecallers')
            ->assertStatus(403);
    }

    public function test_an_anonymous_caller_gets_nothing(): void
    {
        $this->asGuestApp()->getJson('/api/v1/assignees/mrs')
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'unauthenticated');

        $this->asGuestApp()->getJson('/api/v1/assignees/telecallers')
            ->assertStatus(401);
    }
}
