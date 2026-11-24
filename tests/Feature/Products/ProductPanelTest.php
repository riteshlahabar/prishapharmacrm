<?php

namespace Tests\Feature\Products;

use App\Modules\Persons\Models\Person;
use App\Modules\Products\Models\Product;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Models\Visit;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * The product master: name, boxes on hand and what a box costs.
 *
 * The rule worth guarding is that stock is the admin's number alone — the
 * apps record what was discussed and never move it.
 */
class ProductPanelTest extends TestCase
{
    public function test_an_admin_lists_products(): void
    {
        Product::factory()->count(3)->create();

        $this->actingAs($this->admin())->get('/products')
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $p) => $p->total() === 3);
    }

    public function test_an_admin_adds_a_product(): void
    {
        $this->actingAs($this->admin())->post('/products', [
            'name' => 'Paracetamol 500mg',
            'box_qty' => '240',
            'cost' => '119.50',
            'is_active' => '1',
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Paracetamol 500mg',
            'box_qty' => 240,
            'cost' => '119.50',
            'is_active' => true,
        ]);
    }

    public function test_the_three_fields_are_required(): void
    {
        $this->actingAs($this->admin())
            ->from('/products/create')
            ->post('/products', [])
            ->assertSessionHasErrors(['name', 'box_qty', 'cost']);
    }

    public function test_a_duplicate_name_is_refused(): void
    {
        Product::factory()->create(['name' => 'Amoxicillin 250mg']);

        $this->actingAs($this->admin())
            ->from('/products/create')
            ->post('/products', ['name' => 'Amoxicillin 250mg', 'box_qty' => 5, 'cost' => 10])
            ->assertSessionHasErrors('name');
    }

    public function test_negative_stock_and_cost_are_refused(): void
    {
        $this->actingAs($this->admin())
            ->from('/products/create')
            ->post('/products', ['name' => 'Odd one', 'box_qty' => -3, 'cost' => -1])
            ->assertSessionHasErrors(['box_qty', 'cost']);
    }

    public function test_an_unused_product_is_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->delete("/products/{$product->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** The line records what was offered that day; deleting would erase it. */
    public function test_a_product_on_a_visit_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        $visit = Visit::factory()->create();
        $visit->products()->create([
            'product_id' => $product->id,
            'box_qty' => 2,
            'unit_cost' => $product->cost,
        ]);

        $this->actingAs($this->admin())->delete("/products/{$product->id}")
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deactivating_keeps_it_but_drops_it_from_the_apps(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->patch("/products/{$product->id}/toggle")
            ->assertSessionHas('success');

        $this->assertFalse($product->refresh()->is_active);

        $region = Region::factory()->create();
        $mr = $this->mr($region);

        $this->actingAsApp($mr)->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonMissing(['name' => $product->name]);
    }

    public function test_the_detail_page_lists_what_was_recorded(): void
    {
        $product = Product::factory()->create(['name' => 'Azithromycin 500mg']);
        $visit = Visit::factory()->create();
        $visit->products()->create([
            'product_id' => $product->id,
            'box_qty' => 3,
            'unit_cost' => '100.00',
        ]);

        $this->actingAs($this->admin())->get("/visits/{$visit->id}")
            ->assertOk()
            ->assertSee('Azithromycin 500mg')
            // 3 boxes at the quoted 100.00.
            ->assertSee('300.00');
    }

    public function test_a_telecaller_without_the_permission_is_refused(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($telecaller)->get('/products')->assertForbidden();
        $this->actingAs($telecaller)->get('/products/create')->assertForbidden();
    }

    /** Stock is the admin's number: nothing an app does may move it. */
    public function test_recording_a_product_on_a_visit_leaves_stock_alone(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $person = Person::factory()->inRegion($region)->assignedToMr($mr)->create();
        $product = Product::factory()->create(['box_qty' => 100]);

        $this->actingAsApp($mr)->postJson('/api/v1/visits', [
            'person_id' => $person->id,
            'purpose' => 'product_detailing',
            'products' => [['product_id' => $product->id, 'box_qty' => 7]],
        ])->assertCreated();

        $this->assertSame(100, $product->refresh()->box_qty);
    }
}
