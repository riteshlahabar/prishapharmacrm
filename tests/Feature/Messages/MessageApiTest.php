<?php

namespace Tests\Feature\Messages;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Messages\Models\Message;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Tests\TestCase;

/**
 * `/api/v1/messages` — in-app messaging, **no Firebase**.
 *
 * One-directional in Phase 5: a telecaller writes, an MR reads. The two halves
 * carry different token abilities, so each app is refused the other's verbs.
 * The MR app polls `unread-count`, which is one indexed COUNT and nothing else.
 */
class MessageApiTest extends TestCase
{
    private Region $region;

    private User $telecaller;

    private User $mr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create();
        $this->telecaller = $this->telecaller([$this->region]);
        $this->mr = $this->mr($this->region);
    }

    public function test_a_telecaller_sends_a_message_to_an_mr_in_their_region(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $this->mr->id,
            'subject' => 'Please visit Dr Iyer',
            'body' => 'He asked for a sample of the new range.',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subject', 'Please visit Dr Iyer')
            ->assertJsonPath('data.is_read', false);

        $message = Message::firstOrFail();
        $this->assertSame($this->telecaller->id, $message->from_user_id);
        $this->assertSame($this->mr->id, $message->to_user_id);
        $this->assertNull($message->read_at);
    }

    /** A sender who could set `read_at` would mark their own message read. */
    public function test_a_sender_cannot_mark_their_own_message_read(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $this->mr->id,
            'subject' => 'Subject',
            'body' => 'Body',
            'read_at' => now()->toDateTimeString(),
            'from_user_id' => $this->mr->id,
        ])->assertStatus(201);

        $message = Message::firstOrFail();
        $this->assertNull($message->read_at);
        $this->assertSame($this->telecaller->id, $message->from_user_id);
    }

    public function test_an_mr_outside_the_senders_regions_is_refused(): void
    {
        $elsewhere = $this->mr(Region::factory()->create());

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $elsewhere->id,
            'subject' => 'Subject',
            'body' => 'Body',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.to_user_id.0', 'That medical rep is not one you can write to.');
    }

    /**
     * The common case: "I spoke to this doctor, their rep should visit".
     * Refusing that over a region mismatch in the data would be a dead end.
     */
    public function test_the_persons_own_rep_may_be_written_to_even_from_another_region(): void
    {
        $elsewhere = $this->mr(Region::factory()->create());
        $person = Person::factory()->inRegion($this->region)->assignedToMr($elsewhere)->create();

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $elsewhere->id,
            'person_id' => $person->id,
            'subject' => 'Their patient asked for you',
            'body' => 'Please call in.',
        ])->assertStatus(201);
    }

    public function test_a_message_cannot_be_addressed_to_another_telecaller(): void
    {
        $peer = $this->telecaller([$this->region]);

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $peer->id,
            'subject' => 'Subject',
            'body' => 'Body',
        ])->assertStatus(422);
    }

    public function test_a_deactivated_rep_cannot_be_written_to(): void
    {
        $dormant = $this->mr($this->region, ['is_active' => false]);

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $dormant->id,
            'subject' => 'Subject',
            'body' => 'Body',
        ])->assertStatus(422);
    }

    public function test_the_subject_and_body_are_required(): void
    {
        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $this->mr->id,
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['subject', 'body']]);
    }

    public function test_an_attached_call_must_be_one_of_the_senders_own(): void
    {
        $other = $this->telecaller([$this->region]);
        $person = Person::factory()->inRegion($this->region)->create();
        $theirCall = CallLog::factory()->forTelecaller($other)->forPerson($person)->create();

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $this->mr->id,
            'call_log_id' => $theirCall->id,
            'subject' => 'Subject',
            'body' => 'Body',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.call_log_id.0', 'That call is not one of yours.');
    }

    public function test_an_attached_person_must_be_in_the_senders_regions(): void
    {
        $elsewhere = Person::factory()->inRegion(Region::factory()->create())->create();

        $this->actingAsApp($this->telecaller)->postJson('/api/v1/messages', [
            'to_user_id' => $this->mr->id,
            'person_id' => $elsewhere->id,
            'subject' => 'Subject',
            'body' => 'Body',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.person_id.0', 'That person is not in your regions.');
    }

    public function test_the_recipient_picker_lists_active_reps_in_the_senders_regions(): void
    {
        $alsoMine = $this->mr($this->region);
        $this->mr(Region::factory()->create());
        $this->mr($this->region, ['is_active' => false]);

        $response = $this->actingAsApp($this->telecaller)->getJson('/api/v1/mrs')->assertOk();

        $ids = array_column($response->json('data'), 'id');
        sort($ids);
        $expected = [$this->mr->id, $alsoMine->id];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function test_the_inbox_shows_only_the_recipients_own_messages(): void
    {
        $otherMr = $this->mr($this->region);

        Message::factory()->count(2)->from($this->telecaller)->to($this->mr)->create();
        Message::factory()->count(3)->from($this->telecaller)->to($otherMr)->create();

        $this->actingAsApp($this->mr)->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_the_inbox_can_be_narrowed_to_unread(): void
    {
        Message::factory()->from($this->telecaller)->to($this->mr)->create();
        Message::factory()->from($this->telecaller)->to($this->mr)->read()->create();

        $this->actingAsApp($this->mr)->getJson('/api/v1/messages?unread=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_unread_count_is_the_polled_endpoint(): void
    {
        Message::factory()->count(3)->from($this->telecaller)->to($this->mr)->create();
        Message::factory()->from($this->telecaller)->to($this->mr)->read()->create();
        Message::factory()->count(5)->from($this->telecaller)->to($this->mr($this->region))->create();

        $this->actingAsApp($this->mr)->getJson('/api/v1/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread', 3);
    }

    public function test_an_empty_inbox_counts_zero_rather_than_failing(): void
    {
        $this->actingAsApp($this->mr)->getJson('/api/v1/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread', 0);
    }

    public function test_the_addressee_marks_a_message_read(): void
    {
        $message = Message::factory()->from($this->telecaller)->to($this->mr)->create();

        $this->actingAsApp($this->mr)->postJson("/api/v1/messages/{$message->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($message->refresh()->read_at);
    }

    /** A retry from a flaky connection is not an error. */
    public function test_marking_an_already_read_message_keeps_the_original_timestamp(): void
    {
        $message = Message::factory()->from($this->telecaller)->to($this->mr)->read()->create();
        $first = $message->read_at;

        $this->actingAsApp($this->mr)->postJson("/api/v1/messages/{$message->id}/read")->assertOk();

        $this->assertSame($first->toDateTimeString(), $message->refresh()->read_at->toDateTimeString());
    }

    public function test_another_mrs_message_cannot_be_marked_read(): void
    {
        $theirs = Message::factory()->from($this->telecaller)->to($this->mr($this->region))->create();

        $this->actingAsApp($this->mr)->postJson("/api/v1/messages/{$theirs->id}/read")
            ->assertStatus(404)
            ->assertJsonPath('errors.reason', 'not_found');

        $this->assertNull($theirs->refresh()->read_at);
    }

    public function test_marking_all_read_clears_only_the_callers_inbox(): void
    {
        $otherMr = $this->mr($this->region);
        Message::factory()->count(2)->from($this->telecaller)->to($this->mr)->create();
        Message::factory()->count(2)->from($this->telecaller)->to($otherMr)->create();

        $this->actingAsApp($this->mr)->postJson('/api/v1/messages/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked', 2);

        $this->assertSame(0, Message::where('to_user_id', $this->mr->id)->whereNull('read_at')->count());
        $this->assertSame(2, Message::where('to_user_id', $otherMr->id)->whereNull('read_at')->count());
    }

    public function test_a_telecaller_reads_back_what_they_sent(): void
    {
        $peer = $this->telecaller([$this->region]);

        Message::factory()->count(2)->from($this->telecaller)->to($this->mr)->create();
        Message::factory()->count(4)->from($peer)->to($this->mr)->create();

        $this->actingAsApp($this->telecaller)->getJson('/api/v1/messages/sent')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_each_app_is_refused_the_others_half(): void
    {
        $mrToken = $this->appTokenFor($this->mr);
        $tcToken = $this->appTokenFor($this->telecaller);

        // Sending and the picker are the telecaller's.
        $this->actingAsApp($mrToken)->postJson('/api/v1/messages', [])->assertStatus(403);
        $this->actingAsApp($mrToken)->getJson('/api/v1/mrs')->assertStatus(403);
        $this->actingAsApp($mrToken)->getJson('/api/v1/messages/sent')->assertStatus(403);

        // The inbox and the poll are the MR's.
        $this->actingAsApp($tcToken)->getJson('/api/v1/messages')->assertStatus(403);
        $this->actingAsApp($tcToken)->getJson('/api/v1/messages/unread-count')->assertStatus(403);
        $this->actingAsApp($tcToken)->postJson('/api/v1/messages/read-all')->assertStatus(403);
        $this->actingAsApp($tcToken)->postJson('/api/v1/messages/1/read')->assertStatus(403);
    }

    public function test_read_state_is_derived_from_the_one_timestamp(): void
    {
        $unread = Message::factory()->from($this->telecaller)->to($this->mr)->create();
        $read = Message::factory()->from($this->telecaller)->to($this->mr)->read()->create();

        $this->assertFalse($unread->isRead());
        $this->assertTrue($read->isRead());
        $this->assertSame('Unread', $unread->readLabel());
    }
}
