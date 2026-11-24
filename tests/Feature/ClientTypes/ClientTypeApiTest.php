<?php

namespace Tests\Feature\ClientTypes;

use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\Regions\Models\Region;
use Tests\TestCase;

/**
 * `GET /api/v1/client-types` — the type dropdown both Add Client forms show.
 *
 * The master is editable from the panel, so the point of the endpoint is that
 * adding a type there makes it postable from both apps with no release.
 */
class ClientTypeApiTest extends TestCase
{
    public function test_both_apps_get_the_active_types_in_the_masters_order(): void
    {
        $region = Region::factory()->create();

        foreach ([$this->mr($region), $this->telecaller([$region])] as $user) {
            $response = $this->actingAsApp($user)
                ->getJson('/api/v1/client-types')
                ->assertOk()
                // The three the product ships with.
                ->assertJsonCount(3, 'data')
                ->assertJsonPath('data.0.slug', 'doctor')
                ->assertJsonPath('data.1.slug', 'hospital')
                ->assertJsonPath('data.2.slug', 'medical');

            // A small picker: a plain array, no `meta`.
            $this->assertArrayNotHasKey('meta', $response->json());
        }
    }

    /** A type added in the panel reaches both apps without a release. */
    public function test_a_type_added_in_the_panel_appears(): void
    {
        $region = Region::factory()->create();
        ClientType::factory()->create(['slug' => 'nursing-home', 'label' => 'Nursing Home']);

        $this->actingAsApp($this->mr($region))
            ->getJson('/api/v1/client-types')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['slug' => 'nursing-home', 'label' => 'Nursing Home']);
    }

    public function test_a_retired_type_is_not_offered(): void
    {
        $region = Region::factory()->create();
        ClientType::factory()->inactive()->create(['slug' => 'retired']);

        $this->actingAsApp($this->telecaller([$region]))
            ->getJson('/api/v1/client-types')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['slug' => 'retired']);
    }

    /**
     * `has_specialization` drives the form: the field is shown only for the
     * types that keep one, which is the same rule `PersonService` applies
     * after the write.
     */
    public function test_each_row_says_whether_it_carries_a_specialization(): void
    {
        $region = Region::factory()->create();

        $types = $this->actingAsApp($this->mr($region))
            ->getJson('/api/v1/client-types')
            ->assertOk()
            ->json('data');

        $flags = array_column($types, 'has_specialization', 'slug');

        $this->assertTrue($flags['doctor']);
        $this->assertFalse($flags['hospital']);
        $this->assertFalse($flags['medical']);
    }

    public function test_an_anonymous_caller_gets_nothing(): void
    {
        $this->asGuestApp()->getJson('/api/v1/client-types')
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'unauthenticated');
    }
}
