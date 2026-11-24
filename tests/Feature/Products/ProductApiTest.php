<?php

namespace Tests\Feature\Products;

use App\Modules\Products\Models\Product;
use App\Modules\Regions\Models\Region;
use Tests\TestCase;

/**
 * `/api/v1/products` — the list both apps fill their dropdown from. Shared,
 * so it is gated on either app ability rather than a single role.
 */
class ProductApiTest extends TestCase
{
    public function test_both_apps_read_the_same_list(): void
    {
        $region = Region::factory()->create();
        Product::factory()->create(['name' => 'Paracetamol 500mg']);

        foreach ([$this->mr($region), $this->telecaller([$region])] as $user) {
            $this->actingAsApp($user)->getJson('/api/v1/products')
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonFragment(['name' => 'Paracetamol 500mg']);
        }
    }

    public function test_only_active_products_are_listed_and_sorted_by_name(): void
    {
        Product::factory()->create(['name' => 'Zinc 50mg']);
        Product::factory()->create(['name' => 'Amoxicillin 250mg']);
        Product::factory()->inactive()->create(['name' => 'Discontinued 10mg']);

        $names = $this->actingAsApp($this->mr())->getJson('/api/v1/products')
            ->assertOk()
            ->json('data.*.name');

        $this->assertSame(['Amoxicillin 250mg', 'Zinc 50mg'], $names);
    }

    /** Money as a string, the way the column holds it. */
    public function test_a_row_carries_the_cost_and_the_stock(): void
    {
        Product::factory()->create(['name' => 'B Complex', 'cost' => '119.50', 'box_qty' => 42]);

        $row = $this->actingAsApp($this->mr())->getJson('/api/v1/products')->assertOk()->json('data.0');

        $this->assertSame('119.50', $row['cost']);
        $this->assertSame(42, $row['box_qty']);
    }

    public function test_a_guest_is_refused(): void
    {
        $this->asGuestApp()->getJson('/api/v1/products')->assertUnauthorized();
    }

    /** A token minted with neither app ability is not an app token. */
    public function test_a_token_without_an_app_ability_is_refused(): void
    {
        $admin = $this->admin();
        $token = $admin->createToken('phpunit', ['something:else'])->plainTextToken;

        $this->actingAsApp($token)->getJson('/api/v1/products')->assertForbidden();
    }
}
