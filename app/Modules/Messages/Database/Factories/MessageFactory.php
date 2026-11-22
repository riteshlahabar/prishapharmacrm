<?php

namespace App\Modules\Messages\Database\Factories;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Messages\Models\Message;
use App\Modules\Persons\Models\Person;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A message from a telecaller to an MR. Unread by default — that is what a
 * message is when it is sent, and the MR app's unread poll is the thing most
 * worth testing.
 *
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'from_user_id' => User::factory()->telecaller(),
            'to_user_id' => User::factory()->mr(),
            'person_id' => null,
            'call_log_id' => null,
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'read_at' => null,
        ];
    }

    public function from(User|int $sender): static
    {
        return $this->state(['from_user_id' => $sender instanceof User ? $sender->id : $sender]);
    }

    public function to(User|int $recipient): static
    {
        return $this->state(['to_user_id' => $recipient instanceof User ? $recipient->id : $recipient]);
    }

    public function about(Person|int $person): static
    {
        return $this->state(['person_id' => $person instanceof Person ? $person->id : $person]);
    }

    public function followingCall(CallLog|int $call): static
    {
        return $this->state(['call_log_id' => $call instanceof CallLog ? $call->id : $call]);
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }

    public function unread(): static
    {
        return $this->state(['read_at' => null]);
    }
}
