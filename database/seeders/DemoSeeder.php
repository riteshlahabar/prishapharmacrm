<?php

namespace Database\Seeders;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Messages\Models\Message;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Database\Factories\UserFactory;
use App\Modules\Users\Models\User;
use App\Modules\Visits\Enums\VisitPurpose;
use App\Modules\Visits\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * A small but complete demo world, so the panel and both apps have something
 * to show: three regions, reps and telecallers, a contact book, and three
 * weeks of presence, visits, calls and messages.
 *
 * **Not** part of `DatabaseSeeder` — that one only seeds permissions, roles
 * and the admin, which a real deployment needs. Run this on purpose:
 *
 *     php artisan db:seed --class=Database\\Seeders\\DemoSeeder
 *
 * Every demo account signs in with the one password below. It refuses to run
 * if `app.env` is `production`, and skips itself if demo rows already exist,
 * so a second run does not double the data.
 */
class DemoSeeder extends Seeder
{
    /** The password every demo account shares. Dev fixtures only. */
    public const PASSWORD = UserFactory::PASSWORD;

    /** Marks the accounts this seeder owns, so a re-run can recognise them. */
    private const DOMAIN = '@demo.prishapharma.local';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoSeeder refuses to run in production.');

            return;
        }

        if (User::where('email', 'like', '%'.self::DOMAIN)->exists()) {
            $this->command?->warn('Demo data already present — nothing to do.');

            return;
        }

        $regions = $this->regions();
        $reps = $this->medicalReps($regions);
        $telecallers = $this->telecallers($regions);
        $persons = $this->persons($regions, $reps, $telecallers);

        $this->presence($reps);
        $this->visits($reps, $persons);
        $calls = $this->calls($telecallers, $persons);
        $this->messages($telecallers, $reps, $persons, $calls);

        $this->command?->info('Demo data seeded.');
        $this->command?->info("  regions: {$regions->count()}  reps: {$reps->count()}  telecallers: {$telecallers->count()}  persons: {$persons->count()}");
        $this->command?->info('  every demo account signs in with: '.self::PASSWORD);
        $this->command?->info('  e.g. '.$reps->first()->email.' (MR app), '.$telecallers->first()->email.' (telecaller app + panel)');
    }

    /** @return Collection<int, Region> */
    private function regions()
    {
        return collect([
            ['Pune West', 'PNW'],
            ['Mumbai Central', 'MMC'],
            ['Nashik', 'NSK'],
        ])->map(fn (array $r) => Region::firstOrCreate(
            ['code' => $r[1]],
            ['name' => $r[0], 'is_active' => true],
        ));
    }

    /** One rep per region — an MR travels a single territory. */
    private function medicalReps($regions)
    {
        $names = ['Asha Kulkarni', 'Rahul Deshmukh', 'Imran Shaikh'];

        return $regions->values()->map(fn (Region $region, int $i) => User::factory()
            ->mr($region)
            ->create([
                'name' => $names[$i],
                'email' => 'mr'.($i + 1).self::DOMAIN,
                // A block of its own, so a real admin's number cannot clash.
                'mobile' => '95000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
            ]));
    }

    /** Two telecallers; the first covers two regions, to exercise the clamp. */
    private function telecallers($regions)
    {
        $first = User::factory()
            ->telecaller()
            ->inRegions(...$regions->take(2)->all())
            ->create([
                'name' => 'Priya Nair',
                'email' => 'tc1'.self::DOMAIN,
                'mobile' => '9510000001',
            ]);

        $second = User::factory()
            ->telecaller()
            ->inRegions($regions->last())
            ->create([
                'name' => 'Vikram Rao',
                'email' => 'tc2'.self::DOMAIN,
                'mobile' => '9510000002',
            ]);

        return collect([$first, $second]);
    }

    /** A contact book of all three types, spread across the regions. */
    private function persons($regions, $reps, $telecallers)
    {
        $persons = collect();

        foreach ($regions->values() as $i => $region) {
            $rep = $reps[$i];
            $telecaller = $telecallers->first(
                fn (User $t) => $t->regions->contains('id', $region->id),
            ) ?? $telecallers->first();

            $persons = $persons->merge(
                Person::factory()->count(4)->inRegion($region)
                    ->assignedToMr($rep)->assignedToTelecaller($telecaller)
                    ->create()
            );

            $persons->push(
                Person::factory()->hospital()->inRegion($region)
                    ->assignedToMr($rep)->assignedToTelecaller($telecaller)->create()
            );

            $persons->push(
                Person::factory()->medical()->inRegion($region)
                    ->assignedToMr($rep)->assignedToTelecaller($telecaller)->create()
            );

            // One deactivated contact per region, so the "active only" filter
            // on both apps has something to actually exclude.
            $persons->push(
                Person::factory()->inactive()->inRegion($region)
                    ->assignedToMr($rep)->create()
            );
        }

        return $persons;
    }

    /** Three weeks of attendance: mostly worked days, one still on duty. */
    private function presence($reps): void
    {
        foreach ($reps as $rep) {
            foreach (range(1, 20) as $daysAgo) {
                $day = now()->subDays($daysAgo);

                // Sundays off, and the odd absence.
                if ($day->isSunday() || $daysAgo % 7 === 3) {
                    continue;
                }

                Attendance::factory()->forMr($rep)->daysAgo($daysAgo)->create();
            }

            // Today: checked in, not yet out.
            Attendance::factory()->forMr($rep)->openShift()->create();
        }
    }

    private function visits($reps, $persons): void
    {
        $purposes = VisitPurpose::cases();

        foreach ($reps as $rep) {
            $theirs = $persons->where('assigned_mr_id', $rep->id)
                ->where('is_active', true)
                ->values();

            if ($theirs->isEmpty()) {
                continue;
            }

            foreach (range(0, 17) as $n) {
                Visit::factory()
                    ->forMr($rep)
                    ->forPerson($theirs[$n % $theirs->count()])
                    ->daysAgo($n % 18)
                    ->purpose($purposes[$n % count($purposes)])
                    ->create();
            }
        }
    }

    /** Calls across every status, including due and future call-backs. */
    private function calls($telecallers, $persons)
    {
        $calls = collect();
        $statuses = CallStatus::cases();

        foreach ($telecallers as $telecaller) {
            $regionIds = $telecaller->regions->pluck('id')->all();
            $reachable = $persons
                ->whereIn('region_id', $regionIds)
                ->where('is_active', true)
                ->values();

            if ($reachable->isEmpty()) {
                continue;
            }

            foreach (range(0, 14) as $n) {
                $person = $reachable[$n % $reachable->count()];
                $status = $statuses[$n % count($statuses)];

                $factory = CallLog::factory()->forTelecaller($telecaller)->forPerson($person);

                $factory = match (true) {
                    // A call-back already due: what the queue opens on.
                    $status === CallStatus::Callback && $n % 2 === 0 => $factory->followUpDue(),
                    $status === CallStatus::Callback => $factory->callback(),
                    default => $factory->status($status)->calledDaysAgo($n % 12),
                };

                $calls->push($factory->create());
            }
        }

        return $calls;
    }

    /** A few messages, some read, each about a real person and call. */
    private function messages($telecallers, $reps, $persons, $calls): void
    {
        foreach ($telecallers as $telecaller) {
            $regionIds = $telecaller->regions->pluck('id')->all();

            $recipients = $reps->whereIn('region_id', $regionIds)->values();
            $mine = $calls->where('telecaller_id', $telecaller->id)->values();

            if ($recipients->isEmpty() || $mine->isEmpty()) {
                continue;
            }

            foreach (range(0, 5) as $n) {
                $call = $mine[$n % $mine->count()];
                $recipient = $recipients[$n % $recipients->count()];

                $factory = Message::factory()
                    ->from($telecaller)
                    ->to($recipient)
                    ->about($call->person_id)
                    ->followingCall($call)
                    ->state([
                        'subject' => 'Please follow up: '.($persons->firstWhere('id', $call->person_id)?->name ?? 'contact'),
                        'body' => 'Spoke to them today — outcome was '
                            .$call->status->label().'. Worth a visit when you are nearby.',
                    ]);

                // Leave the newest unread so the MR app's badge has something.
                $calls->count() && $n % 3 === 0
                    ? $factory->create()
                    : $factory->read()->create();
            }
        }
    }
}
