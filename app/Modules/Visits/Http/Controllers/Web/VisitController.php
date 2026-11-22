<?php

namespace App\Modules\Visits\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Visits\Enums\VisitPurpose;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin visit report. Visits are filed by the MR app, so the panel reads and
 * (with the permission) deletes, but never creates.
 *
 * The list, the summary counters and the detail page all go through the
 * *ForUser methods, so a telecaller with visits.view sees only visits on
 * persons in their own regions — and cannot reach one outside them by id.
 */
class VisitController extends Controller
{
    public function __construct(
        private readonly VisitServiceInterface $visits,
        private readonly RegionServiceInterface $regions,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'mr_id' => $request->input('mr_id'),
            'person_id' => $request->input('person_id'),
            'region_id' => $request->input('region_id'),
            'purpose' => $request->string('purpose')->value(),
            'search' => $request->string('search')->trim()->value(),
            'without_location' => $request->boolean('without_location'),
        ];

        return view('visits::index', [
            'visits' => $this->visits->paginateForUser($request->user(), $filters, 25),
            'summary' => $this->visits->summaryForUser($request->user(), $filters),
            'filters' => $filters,
            'purposes' => VisitPurpose::cases(),
            'regionOptions' => $this->regions->activeOptions(),
            'mrOptions' => $this->users->activeOfRole(UserRole::Mr),
        ]);
    }

    public function show(Request $request, int|string $visit): View
    {
        return view('visits::show', [
            'visit' => $this->visits->findForUser($request->user(), $visit),
        ]);
    }

    /** Resolved through the scope first: you cannot delete what you cannot see. */
    public function destroy(Request $request, int|string $visit): RedirectResponse
    {
        $this->visits->delete($this->visits->findForUser($request->user(), $visit)->id);

        return redirect()->route('visits.index')->with('success', 'Visit deleted.');
    }
}
