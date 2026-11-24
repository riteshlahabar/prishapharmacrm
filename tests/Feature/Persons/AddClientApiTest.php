<?php

namespace Tests\Feature\Persons;

use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Enums\UserRole;
use Tests\TestCase;

/**
 * `POST /api/v1/persons` — Add Client, from either app.
 *
 * The shape of the feature: whoever adds the client is one of its two
 * assignees automatically and names the other from a dropdown. So three
 * things are never the device's to decide — the caller's own assignment, the
 * region and `is_active` — and the counterpart is required.
 */
class AddClientApiTest extends TestCase
{
    /** @return array<string, mixed> */
    private function body(array $overrides = []): array
    {
        return [
            'name' => 'Dr Asha Kulkarni',
            'type' => 'doctor',
            'mobile' => '9876543210',
            'email' => 'asha@example.com',
            'city' => 'Pune',
            'address' => '12 Clinic Road',
            'specialization' => 'Cardiology',
            ...$overrides,
        ];
    }

    public function test_an_mr_adds_a_client_and_is_its_mr(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $telecaller = $this->telecaller();

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'assigned_telecaller_id' => $telecaller->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Dr Asha Kulkarni')
            ->assertJsonPath('data.assigned_mr.id', $mr->id)
            ->assertJsonPath('data.assigned_telecaller.id', $telecaller->id)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('persons', [
            'mobile' => '9876543210',
            'assigned_mr_id' => $mr->id,
            'assigned_telecaller_id' => $telecaller->id,
            // The MR's own territory, never sent by the device.
            'region_id' => $region->id,
            'is_active' => true,
        ]);
    }

    public function test_a_telecaller_adds_a_client_and_is_its_telecaller(): void
    {
        $mrRegion = Region::factory()->create();
        $mr = $this->mr($mrRegion);
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAsApp($telecaller)
            ->postJson('/api/v1/persons', $this->body(['assigned_mr_id' => $mr->id]))
            ->assertCreated()
            ->assertJsonPath('data.assigned_mr.id', $mr->id)
            ->assertJsonPath('data.assigned_telecaller.id', $telecaller->id);

        // The region follows the MR, who is the one with a territory — not
        // the telecaller, who covers several.
        $this->assertDatabaseHas('persons', [
            'mobile' => '9876543210',
            'assigned_mr_id' => $mr->id,
            'assigned_telecaller_id' => $telecaller->id,
            'region_id' => $mrRegion->id,
        ]);
    }

    /** An MR with no territory yet still gets a client, filed nowhere. */
    public function test_a_client_added_by_a_region_less_mr_has_no_region(): void
    {
        $mr = $this->mr();

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'assigned_telecaller_id' => $this->telecaller()->id,
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('persons', ['mobile' => '9876543210', 'region_id' => null]);
    }

    public function test_the_counterpart_is_required_in_both_apps(): void
    {
        $this->actingAsApp($this->mr(Region::factory()->create()))
            ->postJson('/api/v1/persons', $this->body())
            ->assertStatus(422)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonValidationErrors('assigned_telecaller_id');

        $this->actingAsApp($this->telecaller())
            ->postJson('/api/v1/persons', $this->body(['mobile' => '9000000001']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_mr_id');
    }

    /** Your own side comes from the token; posting it is a 422, not a silent drop. */
    public function test_neither_app_may_post_its_own_assignment(): void
    {
        $mr = $this->mr(Region::factory()->create());
        $telecaller = $this->telecaller();

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'assigned_telecaller_id' => $telecaller->id,
                'assigned_mr_id' => $this->mr()->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_mr_id');

        $this->actingAsApp($telecaller)
            ->postJson('/api/v1/persons', $this->body([
                'mobile' => '9000000002',
                'assigned_mr_id' => $mr->id,
                'assigned_telecaller_id' => $this->telecaller()->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_telecaller_id');
    }

    /** A device must not be able to file a client into a territory it picks. */
    public function test_region_and_active_state_are_refused_from_the_body(): void
    {
        $mr = $this->mr(Region::factory()->create());
        $elsewhere = Region::factory()->create();

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'assigned_telecaller_id' => $this->telecaller()->id,
                'region_id' => $elsewhere->id,
                'is_active' => false,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['region_id', 'is_active']);
    }

    public function test_the_counterpart_must_hold_the_role_the_field_names(): void
    {
        $mr = $this->mr(Region::factory()->create());

        // A telecaller id in the MR field, and the reverse.
        $this->actingAsApp($this->telecaller())
            ->postJson('/api/v1/persons', $this->body([
                'assigned_mr_id' => $this->telecaller()->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_mr_id');

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'mobile' => '9000000003',
                'assigned_telecaller_id' => $this->mr()->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_telecaller_id');
    }

    /** A switched-off account cannot be handed new work. */
    public function test_a_deactivated_counterpart_is_refused(): void
    {
        $mr = $this->mr(Region::factory()->create());
        $telecaller = $this->telecaller([], ['is_active' => false]);

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'assigned_telecaller_id' => $telecaller->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_telecaller_id');
    }

    public function test_a_mobile_already_on_the_books_is_refused(): void
    {
        $mr = $this->mr(Region::factory()->create());
        Person::factory()->create(['mobile' => '9876543210']);

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'assigned_telecaller_id' => $this->telecaller()->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('mobile');
    }

    /** The type master is the vocabulary; a retired type is not postable. */
    public function test_only_an_active_client_type_may_be_posted(): void
    {
        $mr = $this->mr(Region::factory()->create());
        $retired = ClientType::factory()->inactive()->create();

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'type' => $retired->slug,
                'assigned_telecaller_id' => $this->telecaller()->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    /** Only a type flagged for it keeps one — same rule as the panel. */
    public function test_specialization_is_dropped_for_a_type_that_does_not_carry_one(): void
    {
        $mr = $this->mr(Region::factory()->create());

        $this->actingAsApp($mr)
            ->postJson('/api/v1/persons', $this->body([
                'type' => 'medical',
                'specialization' => 'Cardiology',
                'assigned_telecaller_id' => $this->telecaller()->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.specialization', null);

        $this->assertDatabaseHas('persons', ['mobile' => '9876543210', 'specialization' => null]);
    }

    public function test_an_anonymous_caller_cannot_add_a_client(): void
    {
        $this->asGuestApp()
            ->postJson('/api/v1/persons', $this->body())
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'unauthenticated');

        $this->assertDatabaseCount('persons', 0);
    }

    /** A token minted without an app ability authenticates but cannot write. */
    public function test_a_token_without_an_app_ability_is_refused(): void
    {
        $mr = $this->mr(Region::factory()->create());

        $this->actingAsAppWithAbility($mr, UserRole::Admin)
            ->postJson('/api/v1/persons', $this->body([
                'assigned_telecaller_id' => $this->telecaller()->id,
            ]))
            ->assertStatus(403);

        $this->assertDatabaseCount('persons', 0);
    }
}
