<?php

namespace App\Modules\Reports\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a report as CSV. Streamed rather than built in memory, because a
 * coverage report over a large contact book is exactly the download that
 * would otherwise exhaust the request's memory limit.
 *
 * No Composer dependency: a report is a flat table of numbers, and `fputcsv`
 * already quotes and escapes correctly.
 */
final class CsvDownload
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function make(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'wb');

            // Excel reads a UTF-8 CSV as the local 8-bit codepage unless the
            // file opens with a BOM, which mangles any non-ASCII name.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $headings);

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            // Reports are per-viewer and per-period; a cached copy served to
            // the next person would leak across territories.
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** A filename that sorts by report and date in a downloads folder. */
    public static function filename(string $report, array $filters): string
    {
        $from = str_replace('-', '', (string) ($filters['date_from'] ?? ''));
        $to = str_replace('-', '', (string) ($filters['date_to'] ?? ''));
        $span = $from !== '' ? "_{$from}-{$to}" : '';

        return "prisha_{$report}{$span}.csv";
    }

    /**
     * Normalises one cell. `null` becomes an em dash rather than an empty
     * cell, so "no data" and "zero" stay distinguishable in a spreadsheet,
     * and a leading `=`, `+`, `-` or `@` is prefixed with a quote: Excel
     * executes those as formulas, which is how a contact's name becomes a
     * CSV injection.
     */
    private static function cell(mixed $value): string
    {
        $text = match (true) {
            $value === null => '—',
            is_bool($value) => $value ? 'yes' : 'no',
            is_array($value) => implode(' ', $value),
            default => (string) $value,
        };

        return preg_match('/^[=+\-@\t\r]/', $text) === 1 ? "'".$text : $text;
    }
}
