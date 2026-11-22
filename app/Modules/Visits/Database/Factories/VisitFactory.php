<?php

namespace App\Modules\Visits\Database\Factories;

use App\Modules\Persons\Models\Person;
use App\Modules\Users\Models\User;
use App\Modules\Visits\Enums\VisitPurpose;
use App\Modules\Visits\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'mr_id' => User::factory()->mr(),
            'person_id' => Person::factory(),
            'visit_date' => now()->toDateString(),
            'purpose' => VisitPurpose::ProductDetailing,
            'in_time' => '10:30',
            'out_time' => '11:15',
            'latitude' => '19.0760000',
            'longitude' => '72.8777000',
            'location_fetched_at' => now(),
            'notes' => fake()->sentence(),
        ];
    }

    public function forMr(User|int $mr): static
    {
        return $this->state(['mr_id' => $mr instanceof User ? $mr->id : $mr]);
    }

    public function forPerson(Person|int $person): static
    {
        return $this->state(['person_id' => $person instanceof Person ? $person->id : $person]);
    }

    public function onDate(string $date): static
    {
        return $this->state(['visit_date' => $date]);
    }

    public function daysAgo(int $days): static
    {
        return $this->state(['visit_date' => now()->subDays($days)->toDateString()]);
    }

    public function purpose(VisitPurpose $purpose): static
    {
        return $this->state(['purpose' => $purpose]);
    }

    /** An entry filed without a GPS fix. */
    public function withoutGeo(): static
    {
        return $this->state([
            'latitude' => null,
            'longitude' => null,
            'location_fetched_at' => null,
        ]);
    }

    /** In-time only, so `durationMinutes()` stays null. */
    public function unfinished(): static
    {
        return $this->state(['out_time' => null]);
    }
}
