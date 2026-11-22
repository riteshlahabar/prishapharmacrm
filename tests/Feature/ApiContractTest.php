<?php

namespace Tests\Feature;

use App\Core\Http\PerPage;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Messages\Models\Message;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use App\Modules\Visits\Models\Visit;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The parts of `/api/v1` that are the same everywhere, asserted in one place
 * rather than once per module (docs/api/v1-common.md). This is the PHP
 * counterpart of `tests/smoke/phase6-api.sh`, which needs a running server.
 *
 * Both Flutter apps decode exactly one envelope, so a module that rolls its
 * own shape breaks them — these tests are what catches that.
 */
class ApiContractTest extends TestCase
{
    private User $mr;

    private User $telecaller;

    private string $mrToken;

    private string $telecallerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $region = Region::factory()->create();
        $this->mr = $this->mr($region);
        $this->telecaller = $this->telecaller([$region]);

        $person = Person::factory()->inRegion($region)->assignedToMr($this->mr)->create();

        // One row on every list, so a paginated response is never empty-by-luck.
        Attendance::factory()->forMr($this->mr)->create();
        Visit::factory()->forMr($this->mr)->forPerson($person)->create();
        CallLog::factory()->forTelecaller($this->telecaller)->forPerson($person)->followUpDue()->create();
        Message::factory()->from($this->telecaller)->to($this->mr)->about($person)->create();

        $this->mrToken = $this->appTokenFor($this->mr);
        $this->telecallerToken = $this->appTokenFor($this->telecaller);
    }

    /** @return array<string, array{string, string}> */
    public static function listEndpoints(): array
    {
        return [
            'visits' => ['/api/v1/visits', 'mr'],
            'attendance history' => ['/api/v1/attendance/history', 'mr'],
            'inbox' => ['/api/v1/messages', 'mr'],
            'persons (mr)' => ['/api/v1/persons', 'mr'],
            'persons (telecaller)' => ['/api/v1/persons', 'telecaller'],
            'call logs' => ['/api/v1/call-logs', 'telecaller'],
            'follow-ups' => ['/api/v1/call-logs/follow-ups', 'telecaller'],
            'sent messages' => ['/api/v1/messages/sent', 'telecaller'],
        ];
    }

    private function token(string $role): string
    {
        return $role === 'mr' ? $this->mrToken : $this->telecallerToken;
    }

    #[DataProvider('listEndpoints')]
    public function test_every_list_carries_the_same_envelope(string $path, string $role): void
    {
        $this->actingAsApp($this->token($role))->getJson($path)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'has_more'],
            ])
            // `errors` is for failures only; its presence on a success would
            // make the apps' single decoder ambiguous.
            ->assertJsonMissingPath('errors');
    }

    #[DataProvider('listEndpoints')]
    public function test_every_list_defaults_to_twenty_per_page(string $path, string $role): void
    {
        $this->actingAsApp($this->token($role))->getJson($path)
            ->assertOk()
            ->assertJsonPath('meta.per_page', PerPage::DEFAULT);
    }

    #[DataProvider('listEndpoints')]
    public function test_every_list_clamps_an_oversized_page_to_the_maximum(string $path, string $role): void
    {
        $this->actingAsApp($this->token($role))->getJson($path.'?per_page=99999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', PerPage::MAX);
    }

    /**
     * `per_page=-5` once reached the database as an unlimited query and
     * answered 500 from a raw SQL syntax error; `0` quietly used Eloquent's
     * own 15. Anything unusable now falls back to the documented default.
     */
    #[DataProvider('listEndpoints')]
    public function test_an_unusable_page_size_falls_back_instead_of_erroring(string $path, string $role): void
    {
        foreach (['-5', '0', 'abc', '', '1,2'] as $value) {
            $this->actingAsApp($this->token($role))->getJson($path.'?per_page='.$value)
                ->assertOk()
                ->assertJsonPath('meta.per_page', PerPage::DEFAULT);
        }
    }

    #[DataProvider('listEndpoints')]
    public function test_every_list_refuses_an_anonymous_caller_in_the_envelope(string $path, string $role): void
    {
        $this->asGuestApp()->getJson($path)
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'unauthenticated');
    }

    public function test_an_unknown_route_answers_not_found_without_leaking_the_path(): void
    {
        $response = $this->asGuestApp()->getJson('/api/v1/nope-not-a-route');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Not found.')
            ->assertJsonPath('errors.reason', 'not_found')
            ->assertJsonMissingPath('trace');

        $this->assertStringNotContainsString('nope-not-a-route', $response->getContent());
    }

    public function test_the_wrong_verb_answers_method_not_allowed(): void
    {
        $this->asGuestApp()->postJson('/api/v1/auth/me')
            ->assertStatus(405)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'method_not_allowed');
    }

    public function test_a_validation_failure_keeps_the_field_errors_beside_the_reason(): void
    {
        $this->actingAsApp($this->mrToken)->postJson('/api/v1/visits', ['purpose' => 'product_detailing'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonStructure(['errors' => ['reason', 'person_id']]);
    }

    /**
     * 403 is the account — your role or app may not use this feature at all.
     * 404 is the row. The two are kept distinct on purpose.
     */
    public function test_a_telecaller_endpoint_refuses_an_mr_token_as_forbidden(): void
    {
        foreach (['/api/v1/call-logs', '/api/v1/call-logs/follow-ups', '/api/v1/messages/sent', '/api/v1/mrs'] as $path) {
            $this->actingAsApp($this->mrToken)->getJson($path)
                ->assertStatus(403)
                ->assertJsonPath('success', false)
                ->assertJsonPath('errors.reason', 'forbidden');
        }
    }

    public function test_an_mr_endpoint_refuses_a_telecaller_token_as_forbidden(): void
    {
        foreach (['/api/v1/visits', '/api/v1/attendance/today', '/api/v1/attendance/history', '/api/v1/messages'] as $path) {
            $this->actingAsApp($this->telecallerToken)->getJson($path)
                ->assertStatus(403)
                ->assertJsonPath('errors.reason', 'forbidden');
        }
    }

    public function test_the_shared_person_list_accepts_either_app(): void
    {
        $this->actingAsApp($this->mrToken)->getJson('/api/v1/persons')->assertOk();
        $this->actingAsApp($this->telecallerToken)->getJson('/api/v1/persons')->assertOk();
    }

    /** The one documented exception: a small picker, plain array, no `meta`. */
    public function test_the_recipient_picker_is_the_one_list_without_meta(): void
    {
        $this->actingAsApp($this->telecallerToken)->getJson('/api/v1/mrs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('meta');
    }

    public function test_paging_walks_the_rows_without_repeating_them(): void
    {
        $region = $this->mr->region;
        $person = Person::factory()->inRegion($region)->assignedToMr($this->mr)->create();

        // 1 from setUp + 4 here = 5 visits, read two at a time.
        Visit::factory()->count(4)->forMr($this->mr)->forPerson($person)->create();

        $first = $this->actingAsApp($this->mrToken)->getJson('/api/v1/visits?per_page=2')->assertOk();
        $second = $this->actingAsApp($this->mrToken)->getJson('/api/v1/visits?per_page=2&page=2')->assertOk();
        $last = $this->actingAsApp($this->mrToken)->getJson('/api/v1/visits?per_page=2&page=3')->assertOk();

        $first->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.from', 1)
            ->assertJsonPath('meta.to', 2)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonCount(2, 'data');

        $last->assertJsonPath('meta.has_more', false)->assertJsonCount(1, 'data');

        $ids = array_merge(
            array_column($first->json('data'), 'id'),
            array_column($second->json('data'), 'id'),
            array_column($last->json('data'), 'id'),
        );

        $this->assertCount(5, array_unique($ids), 'paging must not repeat or drop a row');
    }

    public function test_a_page_past_the_end_is_empty_rather_than_an_error(): void
    {
        $this->actingAsApp($this->mrToken)->getJson('/api/v1/visits?page=99')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.from', null)
            ->assertJsonPath('meta.to', null)
            ->assertJsonPath('meta.has_more', false);
    }
}
