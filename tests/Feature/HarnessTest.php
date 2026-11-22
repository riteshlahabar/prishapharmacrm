<?php

namespace Tests\Feature;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Messages\Models\Message;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Models\Visit;
use Tests\TestCase;

/**
 * The test harness checking itself: the factories compose into a coherent
 * world and the role helpers grant what they claim. When a module suite fails
 * in a way that makes no sense, run this first — if it is red, the fixtures
 * are wrong, not the application.
 */
class HarnessTest extends TestCase
{
    public function test_factories_and_helpers_build_a_world(): void
    {
        $region = Region::factory()->create();
        $admin = $this->admin();
        $mr = $this->mr($region);
        $tc = $this->telecaller([$region]);

        $person = Person::factory()->inRegion($region)->assignedToMr($mr)->create();
        Attendance::factory()->forMr($mr)->create();
        Visit::factory()->forMr($mr)->forPerson($person)->create();
        $call = CallLog::factory()->forTelecaller($tc)->forPerson($person)->callback()->create();
        Message::factory()->from($tc)->to($mr)->about($person)->followingCall($call)->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->can('persons.view'), 'admin should hold every permission');
        $this->assertTrue($tc->can('persons.view'), 'telecaller should hold role defaults');
        $this->assertFalse($mr->can('persons.view'), 'mr is app-only');
        $this->assertSame([$region->id], $tc->regions->pluck('id')->all());
        $this->assertSame($region->id, $mr->region_id);
        $this->assertTrue($call->hasOpenFollowUp());
    }

    public function test_app_token_reaches_an_api_route(): void
    {
        $mr = $this->mr(Region::factory()->create());

        $this->actingAsApp($mr)->getJson('/api/v1/visits')->assertOk();
    }
}
