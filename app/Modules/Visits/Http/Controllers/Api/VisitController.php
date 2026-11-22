<?php

namespace App\Modules\Visits\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Visits\Http\Requests\StoreVisitRequest;
use App\Modules\Visits\Http\Resources\VisitResource;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/visits` — the MR app files a visit and reads its own history.
 *
 * Both methods are scoped to the authenticated MR, so there is no way to
 * file against, or read, another rep's work.
 */
class VisitController extends Controller
{
    public function __construct(private readonly VisitServiceInterface $visits) {}

    public function store(StoreVisitRequest $request): JsonResponse
    {
        $visit = $this->visits->createFor($request->user(), $request->payload());

        return ApiResponse::success(
            new VisitResource($visit->load('person')),
            'Visit recorded.',
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->visits->historyFor($request->user(), [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'person_id' => $request->input('person_id'),
            'purpose' => $request->input('purpose'),
        ], (int) $request->integer('per_page', 20));

        // Through the resource, so a list item is the same shape as a single
        // one and no column leaks just because it is in a list.
        return ApiResponse::paginated(
            $page->through(fn ($visit) => (new VisitResource($visit))->resolve()),
            'Visits loaded.',
        );
    }
}
