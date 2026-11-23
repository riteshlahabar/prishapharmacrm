<?php

namespace App\Modules\Reports\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\ClientTypes\Services\Contracts\ClientTypeServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Reports\Services\Contracts\ReportServiceInterface;
use App\Modules\Reports\Support\CsvDownload;
use App\Modules\Reports\Support\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * One action per surface: an index of the reports, a report, and its CSV.
 *
 * The controller validates and delegates. Every figure comes from
 * ReportService, which only exposes viewer-clamped methods — so this class
 * cannot accidentally render a company-wide report to a scoped telecaller.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportServiceInterface $reports,
        private readonly RegionServiceInterface $regions,
        private readonly RegionAssignmentServiceInterface $assignment,
        private readonly ClientTypeServiceInterface $clientTypes,
    ) {}

    /** The landing page: what reports exist, and what each one answers. */
    public function index(): View
    {
        return view('reports::index', ['reports' => ReportDefinition::all()]);
    }

    public function show(Request $request, string $report): View
    {
        $definition = $this->definition($report);
        $filters = $this->filters($request);
        $data = $this->build($request, $report, $filters);

        return view($report === 'register' ? 'reports::register' : 'reports::table', [
            'slug' => $report,
            'definition' => $definition,
            'filters' => $filters,
            'data' => $data,
            // A scoped viewer only ever gets their own regions in the picker,
            // so the filter cannot be used to probe outside the clamp.
            'regionOptions' => $request->user()->isAdmin()
                ? $this->regions->activeOptions()
                : $this->assignment->regionsFor($request->user()),
            'types' => $this->clientTypes->activeOptions(),
        ]);
    }

    /**
     * The same rows the screen shows, as CSV — built from the same service
     * call and the same column list, so the download cannot disagree with it.
     */
    public function export(Request $request, string $report): StreamedResponse
    {
        $definition = $this->definition($report);
        $filters = $this->filters($request);
        $data = $this->build($request, $report, $filters);

        if ($report === 'register') {
            return $this->exportRegister($data, $filters);
        }

        $columns = $definition['columns'];

        return CsvDownload::make(
            CsvDownload::filename($report, $filters),
            array_column($columns, 'label'),
            array_map(
                fn (array $row) => array_map(fn (string $key) => $row[$key] ?? null, array_keys($columns)),
                $data['rows'],
            ),
        );
    }

    /** The register's columns are its days, so it builds its own heading row. */
    private function exportRegister(array $data, array $filters): StreamedResponse
    {
        $headings = array_merge(
            ['Medical Rep', 'Region'],
            array_map(fn (array $day) => $day['date'], $data['days']),
            ['Present', 'Absent'],
        );

        $rows = array_map(
            fn (array $row) => array_merge(
                [$row['name'], $row['region']],
                $row['cells'],
                [$row['present'], $row['absent']],
            ),
            $data['rows'],
        );

        return CsvDownload::make(CsvDownload::filename('register', $filters), $headings, $rows);
    }

    private function build(Request $request, string $report, array $filters): array
    {
        $viewer = $request->user();

        return match ($report) {
            'mr' => $this->reports->mrPerformance($viewer, $filters),
            'telecaller' => $this->reports->telecallerPerformance($viewer, $filters),
            'coverage' => $this->reports->coverage($viewer, $filters),
            'region' => $this->reports->regionRollup($viewer, $filters),
            'register' => $this->reports->attendanceRegister($viewer, $filters),
            'follow-ups' => $this->reports->followUpCompliance($viewer, $filters),
        };
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $this->reports->period(
            $request->query('date_from'),
            $request->query('date_to'),
        ) + [
            'sort' => $request->string('sort')->trim()->value(),
            'search' => $request->string('search')->trim()->value(),
            'region_id' => $request->input('region_id') ?: null,
            'type' => $request->string('type')->value() ?: null,
            'stale_days' => $request->input('stale_days') ?: null,
        ];
    }

    /** An unknown slug is a 404, not a blank page. */
    private function definition(string $report): array
    {
        return ReportDefinition::find($report)
            ?? throw new NotFoundHttpException("No such report: {$report}");
    }
}
