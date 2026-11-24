<?php

namespace Tests\Feature;

use App\Modules\Calls\Models\CallLog;
use App\Modules\ClientTypes\Models\ClientType;
use App\Modules\Messages\Models\Message;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Models\Visit;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The action column's markup contract (docs/ui-actions.md).
 *
 * These are CSS hooks, which nothing else here would notice going missing —
 * and both assertions are a defect we actually shipped: the tiles were laid
 * out inline with no wrapper, so the <form> buttons sat half a line below the
 * links, and delete carried Cuba's `.txt-danger`, which is `!important` and
 * so beat every colour rule aimed at it.
 */
class ActionColumnTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function indexRoutes(): array
    {
        return [
            'clients' => ['/clients'],
            'client types' => ['/client-types'],
            'regions' => ['/regions'],
            'telecallers' => ['/telecallers'],
            'visits' => ['/visits'],
            'calls' => ['/calls'],
            'messages' => ['/messages'],
        ];
    }

    #[DataProvider('indexRoutes')]
    public function test_an_index_wraps_its_action_tiles_in_a_flex_row(string $url): void
    {
        $html = $this->renderIndex($url);

        $this->assertStringContainsString('class="table-actions"', $html,
            "{$url}: the action cell needs the .table-actions wrapper, or the form buttons drop below the links");
        $this->assertStringContainsString('btn btn-link btn-icon', $html,
            "{$url}: action controls are .btn-icon tiles");
    }

    #[DataProvider('indexRoutes')]
    public function test_a_delete_tile_never_uses_cubas_txt_danger(string $url): void
    {
        $html = $this->renderIndex($url);

        $this->assertStringContainsString('btn-icon-danger', $html,
            "{$url}: delete is .btn-icon-danger");
        $this->assertStringNotContainsString('btn-icon btn-sm txt-danger', $html,
            "{$url}: .txt-danger is color !important in Cuba — no hover rule can beat it");
    }

    /**
     * Seeds enough that every index under test renders a body rather than its
     * empty state, then returns the page. The tiles only exist on a row.
     */
    private function renderIndex(string $url): string
    {
        $region = Region::factory()->create();
        $mr = $this->mr($region);
        $telecaller = $this->telecaller([$region]);
        $person = Person::factory()->inRegion($region)->assignedToMr($mr)->create();

        // A type of our own: the three built-ins cannot be deleted, so a table
        // holding only those renders no delete tile at all.
        ClientType::factory()->create();

        Visit::factory()->forMr($mr)->forPerson($person)->create();
        $call = CallLog::factory()->forTelecaller($telecaller)->forPerson($person)->create();
        Message::factory()->from($telecaller)->to($mr)->about($person)->followingCall($call)->create();

        return $this->actingAs($this->admin())->get($url)->assertOk()->getContent();
    }
}
