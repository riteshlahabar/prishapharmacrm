<?php

namespace Tests\Feature\Reports;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Persons\Models\Person;
use App\Modules\Regions\Models\Region;
use App\Modules\Visits\Models\Visit;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * CSV download. Built from the same service call and the same column list as
 * the screen, so the file cannot disagree with what the viewer was shown —
 * including the territory clamp.
 */
class ReportExportTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function reportSlugs(): array
    {
        return [
            'mr' => ['mr'],
            'telecaller' => ['telecaller'],
            'coverage' => ['coverage'],
            'region' => ['region'],
            'register' => ['register'],
            'follow-ups' => ['follow-ups'],
        ];
    }

    #[DataProvider('reportSlugs')]
    public function test_every_report_downloads_as_csv(string $slug): void
    {
        $response = $this->actingAs($this->admin())->get("/reports/{$slug}/export");

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();
    }

    public function test_the_filename_carries_the_report_and_its_period(): void
    {
        $this->actingAs($this->admin())
            ->get('/reports/mr/export?date_from=2026-11-01&date_to=2026-11-30')
            ->assertDownload('prisha_mr_20261101-20261130.csv');
    }

    public function test_the_file_opens_with_a_utf8_bom_so_excel_reads_names_correctly(): void
    {
        $body = $this->actingAs($this->admin())->get('/reports/mr/export')->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
    }

    public function test_the_headings_match_the_reports_columns(): void
    {
        $body = $this->actingAs($this->admin())->get('/reports/mr/export')->streamedContent();
        $heading = strtok(ltrim($body, "\xEF\xBB\xBF"), "\n");

        foreach (['Medical Rep', 'Region', 'Days present', 'Visits', 'Coverage'] as $label) {
            $this->assertStringContainsString($label, $heading);
        }
    }

    public function test_the_rows_carry_the_figures_from_the_screen(): void
    {
        Carbon::setTestNow('2026-11-22 12:00:00');

        $region = Region::factory()->named('Pune West', 'PNW')->create();
        $mr = $this->mr($region, ['name' => 'Asha Rep']);
        $person = Person::factory()->inRegion($region)->assignedToMr($mr)->create();

        Attendance::factory()->forMr($mr)->daysAgo(1)->create();
        Visit::factory()->count(2)->forMr($mr)->forPerson($person)->daysAgo(1)->create();

        $body = $this->actingAs($this->admin())->get('/reports/mr/export')->streamedContent();

        $this->assertStringContainsString('Asha Rep', $body);
        $this->assertStringContainsString('Pune West', $body);
    }

    public function test_the_register_export_names_a_column_per_day(): void
    {
        $this->mr(Region::factory()->create());

        $body = $this->actingAs($this->admin())
            ->get('/reports/register/export?date_from=2026-11-20&date_to=2026-11-22')
            ->streamedContent();

        $heading = strtok(ltrim($body, "\xEF\xBB\xBF"), "\n");

        $this->assertStringContainsString('2026-11-20', $heading);
        $this->assertStringContainsString('2026-11-22', $heading);
        $this->assertStringContainsString('Present', $heading);
    }

    /**
     * A contact whose name starts with `=` is a formula to Excel. Prefixing
     * it with a quote is what stops a CSV download executing on open.
     */
    public function test_a_formula_in_a_name_is_neutralised(): void
    {
        $region = Region::factory()->create();
        Person::factory()->inRegion($region)->create(['name' => '=cmd|calc']);

        $body = $this->actingAs($this->admin())->get('/reports/coverage/export')->streamedContent();

        $this->assertStringContainsString("'=cmd|calc", $body);
        // Never the bare formula at the start of a field.
        $this->assertStringNotContainsString(',=cmd|calc', $body);
    }

    /** A report is per-viewer: a cached copy would cross territories. */
    public function test_an_export_is_never_cached(): void
    {
        // Laravel reorders the directives, so assert on their presence.
        $header = $this->actingAs($this->admin())->get('/reports/mr/export')
            ->assertOk()
            ->headers->get('cache-control');

        foreach (['no-store', 'no-cache', 'must-revalidate'] as $directive) {
            $this->assertStringContainsString($directive, $header);
        }
    }

    public function test_the_download_honours_the_same_clamp_as_the_screen(): void
    {
        $mine = Region::factory()->create();
        $theirs = Region::factory()->create();

        $telecaller = $this->telecaller([$mine]);
        $this->grant($telecaller, 'reports.export');

        $this->mr($mine, ['name' => 'Rep In Scope']);
        $this->mr($theirs, ['name' => 'Rep Out Of Scope']);

        $body = $this->actingAs($telecaller)->get('/reports/mr/export')->streamedContent();

        $this->assertStringContainsString('Rep In Scope', $body);
        $this->assertStringNotContainsString('Rep Out Of Scope', $body);
    }

    public function test_the_coverage_download_stays_inside_the_viewers_regions(): void
    {
        $mine = Region::factory()->create();
        $telecaller = $this->telecaller([$mine]);
        $this->grant($telecaller, 'reports.export');

        Person::factory()->inRegion($mine)->create(['name' => 'Gap In Scope']);
        Person::factory()->inRegion(Region::factory()->create())->create(['name' => 'Gap Out Of Scope']);

        $body = $this->actingAs($telecaller)->get('/reports/coverage/export')->streamedContent();

        $this->assertStringContainsString('Gap In Scope', $body);
        $this->assertStringNotContainsString('Gap Out Of Scope', $body);
    }

    /** Viewing and downloading are separate rights: a CSV leaves the panel. */
    public function test_viewing_a_report_does_not_grant_downloading_it(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($telecaller)->get('/reports/mr')->assertOk();
        $this->actingAs($telecaller)->get('/reports/mr/export')->assertForbidden();
    }

    public function test_a_granted_telecaller_may_download(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->grant($telecaller, 'reports.export');

        $this->actingAs($telecaller)->get('/reports/mr/export')->assertOk();
    }

    public function test_an_mr_cannot_download(): void
    {
        $this->actingAs($this->mr())->get('/reports/mr/export')->assertForbidden();
    }

    public function test_a_guest_cannot_download(): void
    {
        $this->get('/reports/mr/export')->assertRedirect(route('login'));
    }

    public function test_an_unknown_report_cannot_be_downloaded(): void
    {
        $this->actingAs($this->admin())->get('/reports/not-a-report/export')->assertNotFound();
    }

    public function test_an_empty_report_still_downloads_with_its_headings(): void
    {
        $body = $this->actingAs($this->admin())->get('/reports/follow-ups/export')->streamedContent();
        $lines = array_filter(explode("\n", trim(ltrim($body, "\xEF\xBB\xBF"))));

        $this->assertCount(1, $lines, 'headings only, but a valid file');
        $this->assertStringContainsString('Promised', $lines[0]);
    }

    public function test_a_null_figure_reads_as_a_dash_not_an_empty_cell(): void
    {
        // A rep with no activity: visits/day has nothing to divide by.
        $this->mr(Region::factory()->create(), ['name' => 'Idle Rep']);

        $body = $this->actingAs($this->admin())->get('/reports/mr/export')->streamedContent();
        $line = collect(explode("\n", $body))->first(fn ($l) => str_contains($l, 'Idle Rep'));

        // "no data" and "zero" must stay distinguishable in a spreadsheet.
        $this->assertStringContainsString('—', $line);
    }

    #[DataProvider('reportSlugs')]
    public function test_the_filters_reach_the_download(string $slug): void
    {
        $this->actingAs($this->admin())
            ->get("/reports/{$slug}/export?date_from=2026-11-01&date_to=2026-11-10")
            ->assertOk()
            ->assertDownload("prisha_{$slug}_20261101-20261110.csv");
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
