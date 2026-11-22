<?php

namespace App\Modules\Persons\Database\Factories;

use App\Modules\Persons\Enums\PersonType;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A doctor, hospital or medical store.
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
            'type' => PersonType::Doctor,
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

    public function ofType(PersonType $type): static
    {
        return $this->state([
            'type' => $type,
            // Only a doctor carries a specialization (PersonType::hasSpecialization).
            'specialization' => $type->hasSpecialization() ? fake()->randomElement(['Cardiology', 'ENT']) : null,
        ]);
    }

    public function hospital(): static
    {
        return $this->ofType(PersonType::Hospital)->state(['name' => fake()->company().' Hospital']);
    }

    public function medical(): static
    {
        return $this->ofType(PersonType::Medical)->state(['name' => fake()->company().' Medicals']);
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
