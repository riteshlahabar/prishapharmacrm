<?php

namespace Tests\Feature\Dashboard;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Models\Visit;
use Tests\TestCase;

/**
 * The panel landing screen. One template for every role: the service asks each
 * module's policy `viewAny` which cards the viewer gets, so there is no role
 * test in the controller or the Blade (docs/modules/dashboard.md).
 */
class DashboardTest extends TestCase
{
    public function test_an_admin_gets_every_card(): void
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $person = Person::factory()->inRegion($region)->create();

        Attendance::factory()->forMr($mr)->create();
        Visit::factory()->forMr($mr)->forPerson($person)->create();
        CallLog::factory()->forTelecaller($this->telecaller([$region]))->forPerson($person)->create();

        $this->actingAs($this->admin())->get('/')
            ->assertOk()
            ->assertViewHas('data', function (array $data) {
                foreach (['visits', 'calls', 'attendance', 'messages', 'persons'] as $card) {
                    $this->assertTrue($data['can'][$card], "admin should see the {$card} card");
                }

                return true;
            });
    }

    public function test_a_telecaller_gets_only_the_cards_their_rights_allow(): void
    {
        $this->actingAs($this->telecaller([Region::factory()->create()]))->get('/')
            ->assertOk()
            ->assertViewHas('data', function (array $data) {
                // Telecaller defaults: persons + calls + messages, not attendance.
                $this->assertTrue($data['can']['persons']);
                $this->assertTrue($data['can']['calls']);
                $this->assertFalse($data['can']['attendance']);
                $this->assertFalse($data['can']['visits']);

                return true;
            });
    }

    /** A granted card appears without any change to the controller or view. */
    public function test_granting_a_permission_adds_its_card(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->grant($telecaller, 'visits.view');

        $this->actingAs($telecaller)->get('/')
            ->assertOk()
            ->assertViewHas('data', function (array $data) {
                $this->assertTrue($data['can']['visits']);

                return true;
            });
    }

    /** Counts are clamped, so a tile and the screen it links to agree. */
    public function test_a_telecallers_counts_stay_inside_their_regions(): void
    {
        $mine = Region::factory()->create();
        $telecaller = $this->telecaller([$mine]);

        Person::factory()->inRegion($mine)->create();
        Person::factory()->count(4)->inRegion(Region::factory()->create())->create();

        $this->actingAs($telecaller)->get('/')
            ->assertOk()
            ->assertViewHas('data', function (array $data) {
                $persons = collect($data['tiles'])->firstWhere('label', 'Clients');

                $this->assertNotNull($persons, 'the clients tile should be present');
                $this->assertSame(1, $persons['value']);
                $this->assertSame('active, your regions', $persons['sub']);

                return true;
            });
    }

    public function test_an_mr_has_no_dashboard(): void
    {
        $this->actingAs($this->mr())->get('/')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
