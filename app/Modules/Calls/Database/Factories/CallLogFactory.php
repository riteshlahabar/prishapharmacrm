<?php

namespace App\Modules\Calls\Database\Factories;

use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Persons\Models\Person;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One telecaller's call on one person.
 *
 * The default is `interested` with no follow-up, because `callback` is the one
 * status that *requires* `follow_up_at` — `callback()` sets both together so a
 * factory row can never break the rule the request enforces.
 *
 * @extends Factory<CallLog>
 */
class CallLogFactory extends Factory
{
    protected $model = CallLog::class;

    public function definition(): array
    {
        return [
            'telecaller_id' => User::factory()->telecaller(),
            'person_id' => Person::factory(),
            'called_at' => now(),
            'status' => CallStatus::Interested,
            'notes' => fake()->sentence(),
            'follow_up_at' => null,
            'follow_up_done_at' => null,
        ];
    }

    public function forTelecaller(User|int $telecaller): static
    {
        return $this->state(['telecaller_id' => $telecaller instanceof User ? $telecaller->id : $telecaller]);
    }

    public function forPerson(Person|int $person): static
    {
        return $this->state(['person_id' => $person instanceof Person ? $person->id : $person]);
    }

    public function status(CallStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function converted(): static
    {
        return $this->status(CallStatus::Converted);
    }

    /** A promised call-back, still open, due in the future. */
    public function callback(?string $followUpAt = null): static
    {
        return $this->state([
            'status' => CallStatus::Callback,
            'follow_up_at' => $followUpAt ?? now()->addDays(2),
            'follow_up_done_at' => null,
        ]);
    }

    /** A call-back whose date has passed: what the follow-up queue shows. */
    public function followUpDue(): static
    {
        return $this->callback()->state(['follow_up_at' => now()->subDay()]);
    }

    public function followUpDone(): static
    {
        return $this->callback()->state(['follow_up_done_at' => now()]);
    }

    public function calledDaysAgo(int $days): static
    {
        return $this->state(['called_at' => now()->subDays($days)]);
    }
}
