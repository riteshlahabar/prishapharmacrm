<?php

namespace App\Modules\Calls\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Calls\Http\Requests\StoreCallLogRequest;
use App\Modules\Calls\Http\Requests\UpdateCallLogRequest;
use App\Modules\Calls\Http\Resources\CallLogResource;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/call-logs` — the telecaller app's working record.
 *
 * Every method is scoped to the authenticated telecaller by the service, so
 * there is no way to read or amend another caller's log (docs/api/
 * v1-telecaller.md).
 */
class CallLogController extends Controller
{
    public function __construct(private readonly CallLogServiceInterface $calls) {}

    public function store(StoreCallLogRequest $request): JsonResponse
    {
        $call = $this->calls->logFor($request->user(), $request->payload());

        return ApiResponse::success(
            new CallLogResource($call->load('person')),
            'Call logged.',
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->calls->historyFor($request->user(), [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'person_id' => $request->input('person_id'),
            'status' => $request->input('status'),
        ], (int) $request->integer('per_page', 20));

        return $this->page($page, 'Call history loaded.');
    }

    /**
     * The call-back queue. Defaults to the ones already due, which is what
     * the app opens on; `?due=0` shows everything still promised.
     */
    public function followUps(Request $request): JsonResponse
    {
        $page = $this->calls->followUpsFor(
            $request->user(),
            $request->boolean('due', true),
            (int) $request->integer('per_page', 20),
        );

        return $this->page($page, 'Follow-ups loaded.');
    }

    public function update(UpdateCallLogRequest $request, int|string $callLog): JsonResponse
    {
        $call = $this->calls->updateFor($request->user(), $callLog, $request->payload());

        return ApiResponse::success(new CallLogResource($call->load('person')), 'Call updated.');
    }

    /** Clears a promised call-back without logging a new call. */
    public function completeFollowUp(Request $request, int|string $callLog): JsonResponse
    {
        $call = $this->calls->completeFollowUp($request->user(), $callLog);

        return ApiResponse::success(new CallLogResource($call->load('person')), 'Follow-up closed.');
    }

    /**
     * Through the resource, so a list item is the same shape as a single one
     * and no column leaks just because it is in a list.
     */
    private function page($paginator, string $message): JsonResponse
    {
        return ApiResponse::paginated(
            $paginator->through(fn ($call) => (new CallLogResource($call->loadMissing('person')))->resolve()),
            $message,
        );
    }
}
