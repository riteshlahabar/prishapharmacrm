<?php

namespace App\Modules\Attendance\Database\Factories;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One MR's day. The default row is a day that was opened and closed, which is
 * what a report mostly contains; `openShift()` and `absent()` cover the other
 * two states the panel renders.
 *
 * `(mr_id, date)` is unique, so a test making several days for one rep must
 * vary the date — `onDate()` or `daysAgo()`.
 *
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = now()->toDateString();

        return [
            'mr_id' => User::factory()->mr(),
            'date' => $date,
            'check_in_at' => now()->setTime(9, 30),
            'check_in_lat' => '19.0760000',
            'check_in_lng' => '72.8777000',
            'check_out_at' => now()->setTime(18, 15),
            'check_out_lat' => '19.0800000',
            'check_out_lng' => '72.8800000',
        ];
    }

    public function forMr(User|int $mr): static
    {
        return $this->state(['mr_id' => $mr instanceof User ? $mr->id : $mr]);
    }

    public function onDate(string $date): static
    {
        return $this->state(['date' => $date]);
    }

    public function daysAgo(int $days): static
    {
        $day = now()->subDays($days);

        return $this->state([
            'date' => $day->toDateString(),
            'check_in_at' => $day->copy()->setTime(9, 30),
            'check_out_at' => $day->copy()->setTime(18, 15),
        ]);
    }

    /** Checked in, not yet out — "On duty". */
    public function openShift(): static
    {
        return $this->state([
            'check_out_at' => null,
            'check_out_lat' => null,
            'check_out_lng' => null,
        ]);
    }

    /** A row with neither stamp, which reads as "Absent". */
    public function absent(): static
    {
        return $this->state([
            'check_in_at' => null,
            'check_in_lat' => null,
            'check_in_lng' => null,
            'check_out_at' => null,
            'check_out_lat' => null,
            'check_out_lng' => null,
        ]);
    }

    /** No coordinates, e.g. a punch with location permission refused. */
    public function withoutGeo(): static
    {
        return $this->state([
            'check_in_lat' => null,
            'check_in_lng' => null,
            'check_out_lat' => null,
            'check_out_lng' => null,
        ]);
    }
}
