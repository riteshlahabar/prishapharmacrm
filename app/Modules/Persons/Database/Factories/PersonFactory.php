<?php

namespace App\Modules\Persons\Database\Factories;

use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A client — a doctor, hospital or medical store by default.
 *
 * `type` is a `client_types.slug`. The three the product ships with are
 * inserted by the client_types migration, so a factory-made client always
 * resolves to a real type without a test seeding one.
 *
 * `region_id` is null by default: region is what scopes both apps, so a test
 * states it explicitly rather than inheriting a region a factory invented.
 *
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'type' => 'doctor',
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->unique()->numerify('8#########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'specialization' => fake()->randomElement(['Cardiology', 'Pediatrics', 'Orthopaedics']),
            'region_id' => null,
            'assigned_mr_id' => null,
            'assigned_telecaller_id' => null,
            'is_active' => true,
        ];
    }

    /** @param  string  $type  a `client_types.slug` */
    public function ofType(string $type): static
    {
        return $this->state([
            'type' => $type,
            // Only the doctor type is flagged as carrying one.
            'specialization' => $type === 'doctor' ? fake()->randomElement(['Cardiology', 'ENT']) : null,
        ]);
    }

    public function hospital(): static
    {
        return $this->ofType('hospital')->state(['name' => fake()->company().' Hospital']);
    }

    public function medical(): static
    {
        return $this->ofType('medical')->state(['name' => fake()->company().' Medicals']);
    }

    public function inRegion(Region|int $region): static
    {
        return $this->state(['region_id' => $region instanceof Region ? $region->id : $region]);
    }

    public function assignedToMr(User|int $mr): static
    {
        return $this->state(['assigned_mr_id' => $mr instanceof User ? $mr->id : $mr]);
    }

    public function assignedToTelecaller(User|int $telecaller): static
    {
        return $this->state(['assigned_telecaller_id' => $telecaller instanceof User ? $telecaller->id : $telecaller]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
