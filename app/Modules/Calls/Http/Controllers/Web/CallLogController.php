<?php

namespace App\Modules\Calls\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Call report. Calls are logged by the telecaller app, so the panel reads and
 * (with the permission) deletes, but never creates.
 *
 * The list, counters and detail all go through the *ForUser methods, which
 * clamp a non-admin to **their own** calls. Unlike visits the scope is the
 * caller rather than the territory: a telecaller's notes are their own
 * working record, not something every colleague in the region reads.
 */
class CallLogController extends Controller
{
    public function __construct(
        private readonly CallLogServiceInterface $calls,
        private readonly RegionServiceInterface $regions,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'telecaller_id' => $request->input('telecaller_id'),
            'region_id' => $request->input('region_id'),
            'status' => $request->string('status')->value(),
            'search' => $request->string('search')->trim()->value(),
            'follow_up' => $request->boolean('follow_up'),
            'follow_up_due' => $request->boolean('follow_up_due'),
        ];

        return view('calls::index', [
            'calls' => $this->calls->paginateForUser($request->user(), $filters, 25),
            'summary' => $this->calls->summaryForUser($request->user(), $filters),
            'filters' => $filters,
            'statuses' => CallStatus::cases(),
            'regionOptions' => $this->regions->activeOptions(),
            'telecallerOptions' => $this->users->activeOfRole(UserRole::Telecaller),
        ]);
    }

    public function show(Request $request, int|string $callLog): View
    {
        return view('calls::show', [
            'call' => $this->calls->findForUser($request->user(), $callLog),
        ]);
    }

    /** Resolved through the scope first: you cannot delete what you cannot see. */
    public function destroy(Request $request, int|string $callLog): RedirectResponse
    {
        $this->calls->delete($this->calls->findForUser($request->user(), $callLog)->id);

        return redirect()->route('calls.index')->with('success', 'Call log deleted.');
    }
}
