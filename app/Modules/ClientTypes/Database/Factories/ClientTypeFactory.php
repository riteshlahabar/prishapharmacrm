<?php

namespace App\Modules\ClientTypes\Database\Factories;

use App\Modules\ClientTypes\Models\ClientType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClientType>
 */
class ClientTypeFactory extends Factory
{
    protected $model = ClientType::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($label),
            'label' => Str::headline($label),
            'colour' => fake()->randomElement(ClientType::COLOURS),
            'has_specialization' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
