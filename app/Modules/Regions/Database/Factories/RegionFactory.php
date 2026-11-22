<?php

namespace App\Modules\Regions\Database\Factories;

use App\Modules\Regions\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            // `code` is unique and capped at 20; a counter keeps a long seeding
            // run from colliding the way a random 3-letter code would.
            'code' => strtoupper(fake()->unique()->bothify('R##??')),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function named(string $name, string $code): static
    {
        return $this->state(['name' => $name, 'code' => strtoupper($code)]);
    }
}
