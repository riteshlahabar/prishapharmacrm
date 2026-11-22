<?php

namespace Tests\Unit;

use App\Modules\Reports\Support\ReportDefinition;
use PHPUnit\Framework\TestCase;

/**
 * The report catalogue drives the table head, the sort links and the CSV from
 * one list, so a column missing here is a column missing from the download.
 * Pure data, so no application boot.
 */
class ReportDefinitionTest extends TestCase
{
    public function test_the_six_reports_are_all_declared(): void
    {
        $this->assertSame(
            ['mr', 'telecaller', 'coverage', 'region', 'follow-ups', 'register'],
            ReportDefinition::slugs(),
        );
    }

    public function test_every_report_has_a_title_and_a_blurb(): void
    {
        foreach (ReportDefinition::all() as $slug => $report) {
            $this->assertNotSame('', $report['title'], "{$slug} needs a title");
            $this->assertNotSame('', $report['blurb'], "{$slug} needs a blurb for the index page");
        }
    }

    /**
     * Every column needs a label, and the first column is the row's subject —
     * the table template renders it bold and links the badge off it.
     */
    public function test_every_column_is_labelled_and_names_its_subject_first(): void
    {
        foreach (ReportDefinition::all() as $slug => $report) {
            if ($report['columns'] === []) {
                // The register's columns are its days; it renders its own grid.
                $this->assertSame('register', $slug);

                continue;
            }

            foreach ($report['columns'] as $key => $column) {
                $this->assertArrayHasKey('label', $column, "{$slug}.{$key} needs a label");
            }

            $this->assertSame('name', array_key_first($report['columns']), "{$slug} should lead with its subject");
        }
    }

    public function test_an_unknown_slug_is_not_found(): void
    {
        $this->assertNull(ReportDefinition::find('nope'));
        $this->assertIsArray(ReportDefinition::find('mr'));
    }
}
