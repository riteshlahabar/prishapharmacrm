<?php

namespace App\Modules\Reports\Support;

/**
 * The six reports in one place: slug, title, and the columns each one shows.
 *
 * The column list drives the table head, the sort links **and** the CSV, so a
 * screen and its download cannot drift apart — a report whose export is
 * missing a column is the classic way these go wrong.
 */
final class ReportDefinition
{
    /**
     * Each column: `key => [label, numeric?, suffix?]`.
     *
     * @return array<string, array{title: string, blurb: string, columns: array<string, array<string, mixed>>}>
     */
    public static function all(): array
    {
        return [
            'mr' => [
                'title' => 'Medical rep performance',
                'blurb' => 'Presence, visits and coverage per rep for the period.',
                'columns' => [
                    'name' => ['label' => 'Medical Rep'],
                    'region' => ['label' => 'Region'],
                    'days_present' => ['label' => 'Days present', 'numeric' => true],
                    'hours' => ['label' => 'Hours', 'numeric' => true],
                    'avg_hours' => ['label' => 'Avg h/day', 'numeric' => true],
                    'visits' => ['label' => 'Visits', 'numeric' => true],
                    'persons_seen' => ['label' => 'Clients seen', 'numeric' => true],
                    'visits_per_day' => ['label' => 'Visits/day', 'numeric' => true],
                    'assigned' => ['label' => 'Assigned', 'numeric' => true],
                    'coverage' => ['label' => 'Coverage', 'numeric' => true, 'suffix' => '%'],
                    'geotagged' => ['label' => 'Geo-tagged', 'numeric' => true, 'suffix' => '%'],
                ],
            ],
            'telecaller' => [
                'title' => 'Telecaller performance',
                'blurb' => 'Calls, conversions and promised call-backs per telecaller.',
                'columns' => [
                    'name' => ['label' => 'Telecaller'],
                    'regions' => ['label' => 'Regions'],
                    'calls' => ['label' => 'Calls', 'numeric' => true],
                    'persons_called' => ['label' => 'Clients', 'numeric' => true],
                    'calls_per_day' => ['label' => 'Calls/day', 'numeric' => true],
                    'converted' => ['label' => 'Converted', 'numeric' => true],
                    'conversion' => ['label' => 'Conversion', 'numeric' => true, 'suffix' => '%'],
                    'interested' => ['label' => 'Interested', 'numeric' => true],
                    'not_reachable' => ['label' => 'Unreachable', 'numeric' => true],
                    'callbacks' => ['label' => 'Call-backs', 'numeric' => true],
                    'overdue' => ['label' => 'Overdue', 'numeric' => true],
                    'messages_sent' => ['label' => 'Msgs to MR', 'numeric' => true],
                ],
            ],
            'coverage' => [
                'title' => 'Coverage gaps',
                'blurb' => 'Active contacts nobody has visited or called recently.',
                'columns' => [
                    'name' => ['label' => 'Client'],
                    'type' => ['label' => 'Type'],
                    'region' => ['label' => 'Region'],
                    'mobile' => ['label' => 'Mobile'],
                    'assigned_mr' => ['label' => 'Assigned rep'],
                    'last_visit' => ['label' => 'Last visit'],
                    'visit_age' => ['label' => 'Days since', 'numeric' => true],
                    'last_call' => ['label' => 'Last call'],
                    'call_age' => ['label' => 'Days since', 'numeric' => true],
                ],
            ],
            'region' => [
                'title' => 'Region rollup',
                'blurb' => 'Headcount, contacts and activity per territory.',
                'columns' => [
                    'name' => ['label' => 'Region'],
                    'code' => ['label' => 'Code'],
                    'mrs' => ['label' => 'Reps', 'numeric' => true],
                    'telecallers' => ['label' => 'Telecallers', 'numeric' => true],
                    'persons' => ['label' => 'Clients', 'numeric' => true],
                    'visits' => ['label' => 'Visits', 'numeric' => true],
                    'calls' => ['label' => 'Calls', 'numeric' => true],
                    'touches_per_person' => ['label' => 'Touches/client', 'numeric' => true],
                ],
            ],
            'follow-ups' => [
                'title' => 'Follow-up compliance',
                'blurb' => 'Were promised call-backs actually made, and on time?',
                'columns' => [
                    'name' => ['label' => 'Telecaller'],
                    'promised' => ['label' => 'Promised', 'numeric' => true],
                    'honoured' => ['label' => 'Honoured', 'numeric' => true],
                    'on_time' => ['label' => 'On time', 'numeric' => true],
                    'late' => ['label' => 'Late', 'numeric' => true],
                    'open' => ['label' => 'Still open', 'numeric' => true],
                    'overdue' => ['label' => 'Overdue', 'numeric' => true],
                    'kept' => ['label' => 'Kept', 'numeric' => true, 'suffix' => '%'],
                    'punctual' => ['label' => 'Punctual', 'numeric' => true, 'suffix' => '%'],
                ],
            ],
            // The register is a grid, not a column list: its columns are the
            // days in the range, so it renders from its own template.
            'register' => [
                'title' => 'Attendance register',
                'blurb' => 'Reps down the side, days across the top. P on duty and out, O still on duty, A absent.',
                'columns' => [],
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /** @return array<int, string> */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
