<?php

namespace App\Modules\Products\Database\Factories;

use App\Modules\Products\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' '.fake()->numberBetween(5, 500).'mg',
            'box_qty' => fake()->numberBetween(0, 400),
            'cost' => fake()->randomFloat(2, 15, 2500),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(['box_qty' => 0]);
    }
}
