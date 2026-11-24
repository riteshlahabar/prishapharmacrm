<?php

namespace Tests\Feature\Products;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Persons\Models\Person;
use App\Modules\Products\Models\Product;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use App\Modules\Visits\Models\Visit;
use Tests\TestCase;

/**
 * Product lines on a visit and on a call log.
 *
 * The one rule that is not obvious: `unit_cost` is a snapshot. A visit read
 * back after a price rise has to show the number that was on the rep's
 * screen that day, not today's.
 */
class ProductLineTest extends TestCase
{
    private Region $region;

    private User $mr;

    private User $telecaller;

    private Person $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create();
        $this->mr = $this->mr($this->region);
        $this->telecaller = $this->telecaller([$this->region]);
        $this->person = Person::factory()
            ->inRegion($this->region)
            ->assignedToMr($this->mr)
            ->create();
    }

    private function visitPayload(array $products = []): array
    {
        return [
            'person_id' => $this->person->id,
            'purpose' => 'product_detailing',
        ] + ($products === [] ? [] : ['products' => $products]);
    }

    public function test_a_visit_records_its_product_lines(): void
    {
        $a = Product::factory()->create(['cost' => '120.00']);
        $b = Product::factory()->create(['cost' => '45.50']);

        $response = $this->actingAsApp($this->mr)->postJson('/api/v1/visits', $this->visitPayload([
            ['product_id' => $a->id, 'box_qty' => 2],
            ['product_id' => $b->id, 'box_qty' => 1],
        ]))->assertCreated();

        $this->assertCount(2, $response->json('data.products'));
        $this->assertDatabaseHas('visit_products', [
            'product_id' => $a->id, 'box_qty' => 2, 'unit_cost' => '120.00',
        ]);
        $this->assertDatabaseHas('visit_products', [
            'product_id' => $b->id, 'box_qty' => 1, 'unit_cost' => '45.50',
        ]);
    }

    public function test_products_are_optional_on_a_visit(): void
    {
        $this->actingAsApp($this->mr)->postJson('/api/v1/visits', $this->visitPayload())
            ->assertCreated()
            ->assertJsonPath('data.products', []);

        $this->assertSame(0, Visit::firstOrFail()->products()->count());
    }

    /** The whole reason the cost is copied onto the line. */
    public function test_a_later_price_change_does_not_rewrite_a_recorded_line(): void
    {
        $product = Product::factory()->create(['cost' => '100.00']);

        $this->actingAsApp($this->mr)->postJson('/api/v1/visits', $this->visitPayload([
            ['product_id' => $product->id, 'box_qty' => 3],
        ]))->assertCreated();

        $product->update(['cost' => '250.00']);

        $line = Visit::firstOrFail()->products()->firstOrFail();

        $this->assertSame('100.00', (string) $line->unit_cost);
        $this->assertSame(300.0, $line->lineTotal());
    }

    /** Tapping + twice for the same product is a quantity, not two rows. */
    public function test_two_lines_for_one_product_are_summed(): void
    {
        $product = Product::factory()->create();

        $this->actingAsApp($this->mr)->postJson('/api/v1/visits', $this->visitPayload([
            ['product_id' => $product->id, 'box_qty' => 2],
            ['product_id' => $product->id, 'box_qty' => 5],
        ]))->assertCreated();

        $lines = Visit::firstOrFail()->products;

        $this->assertCount(1, $lines);
        $this->assertSame(7, $lines->first()->box_qty);
    }

    public function test_an_unknown_product_or_an_empty_box_qty_is_refused(): void
    {
        $this->actingAsApp($this->mr)
            ->postJson('/api/v1/visits', $this->visitPayload([['product_id' => 9999, 'box_qty' => 1]]))
            ->assertStatus(422)
            ->assertJsonFragment(['One of those products no longer exists.']);

        $product = Product::factory()->create();

        $this->actingAsApp($this->mr)
            ->postJson('/api/v1/visits', $this->visitPayload([['product_id' => $product->id, 'box_qty' => 0]]))
            ->assertStatus(422);
    }

    public function test_a_call_log_records_its_product_lines(): void
    {
        $product = Product::factory()->create(['cost' => '80.00']);

        $response = $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'interested',
            'notes' => 'Wants a quote.',
            'products' => [['product_id' => $product->id, 'box_qty' => 4]],
        ])->assertCreated();

        $this->assertSame(4, $response->json('data.products.0.box_qty'));
        $this->assertSame('320.00', $response->json('data.products.0.line_total'));
        $this->assertDatabaseHas('call_log_products', [
            'product_id' => $product->id, 'box_qty' => 4, 'unit_cost' => '80.00',
        ]);
    }

    public function test_products_are_optional_on_a_call(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'not_reachable',
        ])->assertCreated()->assertJsonPath('data.products', []);
    }

    /** A PATCH that changes the outcome alone leaves the lines alone. */
    public function test_amending_without_products_keeps_the_lines(): void
    {
        $product = Product::factory()->create();

        $call = $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'interested',
            'products' => [['product_id' => $product->id, 'box_qty' => 2]],
        ])->assertCreated()->json('data.id');

        $this->actingAsApp($this->telecaller)
            ->patchJson("/api/v1/call-logs/{$call}", ['status' => 'converted'])
            ->assertOk()
            ->assertJsonCount(1, 'data.products');

        $this->assertSame(1, CallLog::findOrFail($call)->products()->count());
    }

    public function test_amending_with_an_empty_list_clears_the_lines(): void
    {
        $product = Product::factory()->create();

        $call = $this->actingAsApp($this->telecaller)->postJson('/api/v1/call-logs', [
            'person_id' => $this->person->id,
            'status' => 'interested',
            'products' => [['product_id' => $product->id, 'box_qty' => 2]],
        ])->assertCreated()->json('data.id');

        $this->actingAsApp($this->telecaller)
            ->patchJson("/api/v1/call-logs/{$call}", ['products' => []])
            ->assertOk()
            ->assertJsonPath('data.products', []);

        $this->assertSame(0, CallLog::findOrFail($call)->products()->count());
    }

    /** Deleting the parent takes its lines; the product itself stays. */
    public function test_deleting_a_visit_takes_its_lines_with_it(): void
    {
        $product = Product::factory()->create();

        $this->actingAsApp($this->mr)->postJson('/api/v1/visits', $this->visitPayload([
            ['product_id' => $product->id, 'box_qty' => 1],
        ]))->assertCreated();

        $visit = Visit::firstOrFail();

        $this->actingAs($this->admin())->delete("/visits/{$visit->id}");

        $this->assertDatabaseCount('visit_products', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
