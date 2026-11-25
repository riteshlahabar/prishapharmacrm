<?php

namespace App\Modules\Visits\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Core\Http\PerPage;
use App\Http\Controllers\Controller;
use App\Modules\Visits\Http\Requests\StoreVisitRequest;
use App\Modules\Visits\Http\Requests\UpdateVisitRequest;
use App\Modules\Visits\Http\Resources\VisitResource;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/visits` — the MR app files a visit, reads its own history, and
 * corrects an entry it got wrong.
 *
 * Every method is scoped to the authenticated MR, so there is no way to file
 * against, read, or amend another rep's work.
 */
class VisitController extends Controller
{
    public function __construct(private readonly VisitServiceInterface $visits) {}

    public function store(StoreVisitRequest $request): JsonResponse
    {
        $visit = $this->visits->createFor($request->user(), $request->payload());

        return ApiResponse::success(
            new VisitResource($visit->load(['person', 'products.product'])),
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
        ], PerPage::from($request));

        // Through the resource, so a list item is the same shape as a single
        // one and no column leaks just because it is in a list.
        return ApiResponse::paginated(
            $page->through(fn ($visit) => (new VisitResource($visit))->resolve()),
            'Visits loaded.',
        );
    }

    /**
     * Corrects a visit this rep filed: the purpose, the clocks, the notes and
     * the product lines. The person, the coordinates and the photo are not
     * amendable — `UpdateVisitRequest` says why. Somebody else's visit is a
     * **404**, never a 403.
     */
    public function update(UpdateVisitRequest $request, int|string $visit): JsonResponse
    {
        $amended = $this->visits->updateFor($request->user(), $visit, $request->payload());

        return ApiResponse::success(new VisitResource($amended), 'Visit updated.');
    }
}
