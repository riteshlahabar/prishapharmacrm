<?php

namespace App\Modules\Attendance\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\Contracts\AttendanceServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin daily presence report. Read-only: attendance is created by the app,
 * never typed into the panel, so there is nothing to edit here.
 *
 * The rows and the counters both go through the *ForUser methods, so a
 * telecaller granted `attendance.view` sees only reps in the regions they
 * cover — as the visits, calls and messages reports already did.
 */
class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceServiceInterface $attendance,
        private readonly RegionServiceInterface $regions,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function index(Request $request): View
    {
        // Defaults to today, which is what "who is out there right now" means.
        $filters = [
            'date_from' => $request->input('date_from', now()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'mr_id' => $request->input('mr_id'),
            'region_id' => $request->input('region_id'),
            'status' => $request->string('status')->value(),
            'search' => $request->string('search')->trim()->value(),
        ];

        return view('attendance::index', [
            'rows' => $this->attendance->paginateForUser($request->user(), $filters, 25),
            'summary' => $this->attendance->summaryForUser($request->user(), $filters),
            'filters' => $filters,
            'regionOptions' => $this->regions->activeOptions(),
            'mrOptions' => $this->users->activeOfRole(UserRole::Mr),
        ]);
    }
}
