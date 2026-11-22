<?php

namespace Tests\Feature\Messages;

use App\Modules\Messages\Models\Message;
use App\Modules\Regions\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * The panel's message view. Messages are written in the apps, so the panel is
 * read-only plus delete.
 *
 * A message has two owners, so a non-admin sees their own either way round —
 * sent or received.
 */
class MessagePanelTest extends TestCase
{
    public function test_an_admin_sees_every_message(): void
    {
        $region = Region::factory()->create();
        Message::factory()->count(2)->from($this->telecaller([$region]))->to($this->mr($region))->create();
        Message::factory()->from($this->telecaller([$region]))->to($this->mr($region))->create();

        $this->actingAs($this->admin())->get('/messages')
            ->assertOk()
            ->assertViewHas('messages', fn (LengthAwarePaginator $p) => $p->total() === 3);
    }

    public function test_a_telecaller_sees_the_messages_they_are_party_to(): void
    {
        $region = Region::factory()->create();
        $mine = $this->telecaller([$region]);
        $mr = $this->mr($region);

        $visible = Message::factory()->from($mine)->to($mr)->create();
        Message::factory()->count(3)->from($this->telecaller([$region]))->to($mr)->create();

        $this->actingAs($mine)->get('/messages')
            ->assertOk()
            ->assertViewHas('messages', fn (LengthAwarePaginator $p) => $p->pluck('id')->all() === [$visible->id]);
    }

    public function test_a_telecaller_opens_their_own_message(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);
        $message = Message::factory()->from($telecaller)->to($this->mr($region))
            ->create(['subject' => 'Visible Subject']);

        $this->actingAs($telecaller)->get("/messages/{$message->id}")
            ->assertOk()
            ->assertSee('Visible Subject');
    }

    public function test_someone_elses_message_reads_as_missing(): void
    {
        $region = Region::factory()->create();
        $outsider = $this->telecaller([$region]);
        $theirs = Message::factory()->from($this->telecaller([$region]))->to($this->mr($region))->create();

        $this->actingAs($outsider)->get("/messages/{$theirs->id}")->assertNotFound();
    }

    public function test_deleting_needs_its_own_permission(): void
    {
        $region = Region::factory()->create();
        $telecaller = $this->telecaller([$region]);
        $message = Message::factory()->from($telecaller)->to($this->mr($region))->create();

        // messages.delete is not a telecaller default.
        $this->actingAs($telecaller)->delete("/messages/{$message->id}")->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id]);
    }

    public function test_an_admin_deletes_a_message(): void
    {
        $region = Region::factory()->create();
        $message = Message::factory()->from($this->telecaller([$region]))->to($this->mr($region))->create();

        $this->actingAs($this->admin())->delete("/messages/{$message->id}")
            ->assertRedirect(route('messages.index'));

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/messages')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/messages')->assertRedirect(route('login'));
    }
}
