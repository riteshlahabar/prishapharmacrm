<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The brand layer's dark-mode contract (docs/ui-brand.md).
 *
 * prisha.css loads *after* Cuba's style.css, so any literal light colour in it
 * beats Cuba's `body.dark-only` rules and survives into dark mode. That is a
 * defect we shipped: the Medical Reps and Telecallers lists kept a white
 * striped table, a white filter panel and near-black headings on a near-black
 * page. Nothing else here would notice these rules going missing, and a rule
 * can only be checked where it lives — hence a test that reads the stylesheet.
 */
class DarkModeCssTest extends TestCase
{
    /**
     * Each entry is a light-mode declaration in prisha.css and the
     * `body.dark-only` selector that has to cancel it.
     *
     * @return array<string, array{string, string}>
     */
    public static function lightRulesNeedingADarkCounterpart(): array
    {
        return [
            'brand tokens' => ['--prisha-ink:', 'body.dark-only {'],
            'striped rows' => [
                '.table.table-striped > tbody > tr:nth-of-type(odd) > *',
                'body.dark-only .table.table-striped > tbody > tr:nth-of-type(odd) > *',
            ],
            'row hover' => [
                '.table.table-hover > tbody > tr:hover > *',
                'body.dark-only .table.table-hover > tbody > tr:hover > *',
            ],
            'filter-bar fields' => ['.filter-bar .form-control,', 'body.dark-only .filter-bar .form-control,'],
            'disabled pager' => [
                '.pagination .page-item.disabled .page-link',
                'body.dark-only .pagination .page-item.disabled .page-link',
            ],
            'mini-card fill' => ['.mini-stat {', 'body.dark-only .mini-stat {'],
            'cancel button' => ['.btn-light', 'body.dark-only .btn-light {'],
            'action tiles' => ['.btn-link.btn-icon {', 'body.dark-only .btn-link.btn-icon {'],
        ];
    }

    #[DataProvider('lightRulesNeedingADarkCounterpart')]
    public function test_a_light_rule_is_cancelled_in_dark_mode(string $light, string $dark): void
    {
        $css = $this->brandStylesheet();

        $this->assertStringContainsString($light, $css,
            "prisha.css no longer has `{$light}` — update this test with the rule that replaced it");
        $this->assertStringContainsString($dark, $css,
            "`{$light}` is a light colour Cuba's dark theme cannot reach: prisha.css loads after style.css. It needs `{$dark}`");
    }

    /**
     * The one place a `#fff` fill is allowed to survive into dark mode is text
     * on the brand gradient — the table head, the active page, the dashboard
     * tiles — which reads on either background. Everything else that paints
     * white has to sit above the dark-mode section, where a counterpart above
     * can cancel it; a new white rule appended below would silently win.
     */
    public function test_the_dark_mode_section_is_last_in_the_file(): void
    {
        $css = $this->brandStylesheet();
        $start = strpos($css, 'body.dark-only {');

        $this->assertNotFalse($start, 'prisha.css has no dark-mode section');
        $this->assertStringNotContainsString('#fff;', substr($css, $start),
            'a light fill was added below the dark-mode section, where nothing above can cancel it');
    }

    private function brandStylesheet(): string
    {
        $path = public_path('assets/css/prisha.css');
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
