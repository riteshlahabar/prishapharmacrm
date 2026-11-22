<?php

namespace Tests\Feature\Calls;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * The panel's call report. Calls are logged by the telecaller app, so there is
 * no create or edit here — only read and (with the permission) delete.
 *
 * A call log is scoped by its **caller**: notes are a personal record, so a
 * telecaller sees their own calls and no one else's.
 */
class CallLogPanelTest extends TestCase
{
    public function test_an_admin_sees_every_call(): void
    {
        $region = Region::factory()->create();
        $person = Person::factory()->inRegion($region)->create();

        CallLog::factory()->count(2)->forTelecaller($this->telecaller([$region]))->forPerson($person)->create();
        CallLog::factory()->forTelecaller($this->telecaller([$region]))->forPerson($person)->create();

        $this->actingAs($this->admin())->get('/calls')
            ->assertOk()
            ->assertViewHas('calls', fn (LengthAwarePaginator $p) => $p->total() === 3);
    }

    public function test_a_telecaller_sees_only_their_own_calls(): void
    {
        $region = Region::factory()->create();
        $person = Person::factory()->inRegion($region)->create();
        $mine = $this->telecaller([$region]);
        $theirs = $this->telecaller([$region]);

        $visible = CallLog::factory()->forTelecaller($mine)->forPerson($person)->create();
        CallLog::factory()->count(3)->forTelecaller($theirs)->forPerson($person)->create();

        $this->actingAs($mine)->get('/calls')
            ->assertOk()
            ->assertViewHas('calls', fn (LengthAwarePaginator $p) => $p->pluck('id')->all() === [$visible->id]);
    }

    public function test_a_telecaller_opens_their_own_call(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);
        $person = Person::factory()->inRegion($region)->create(['name' => 'Dr Shown']);
        $call = CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->create();

        $this->actingAs($telecaller)->get("/calls/{$call->id}")
            ->assertOk()
            ->assertSee('Dr Shown');
    }

    /** Missing, not forbidden — the response must not confirm the row exists. */
    public function test_another_telecallers_call_reads_as_missing(): void
    {
        $region = Region::factory()->create();
        $person = Person::factory()->inRegion($region)->create();
        $mine = $this->telecaller([$region]);
        $theirs = CallLog::factory()->forTelecaller($this->telecaller([$region]))->forPerson($person)->create();

        $this->actingAs($mine)->get("/calls/{$theirs->id}")->assertNotFound();
    }

    public function test_deleting_needs_its_own_permission(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);
        $person = Person::factory()->inRegion($region)->create();
        $call = CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->create();

        // calls.delete is not a telecaller default.
        $this->actingAs($telecaller)->delete("/calls/{$call->id}")->assertForbidden();

        $this->assertDatabaseHas('call_logs', ['id' => $call->id]);
    }

    public function test_an_admin_deletes_a_mistaken_entry(): void
    {
        $region = Region::factory()->create();
        $call = CallLog::factory()
            ->forTelecaller($this->telecaller([$region]))
            ->forPerson(Person::factory()->inRegion($region)->create())
            ->create();

        $this->actingAs($this->admin())->delete("/calls/{$call->id}")
            ->assertRedirect(route('calls.index'));

        $this->assertDatabaseMissing('call_logs', ['id' => $call->id]);
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/calls')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/calls')->assertRedirect(route('login'));
    }
}
