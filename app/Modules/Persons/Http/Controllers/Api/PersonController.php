<?php

namespace App\Modules\Persons\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Core\Http\PerPage;
use App\Http\Controllers\Controller;
use App\Modules\Calls\Http\Resources\CallLogResource;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;
use App\Modules\Persons\Http\Requests\StoreAppPersonRequest;
use App\Modules\Persons\Http\Requests\UpdateAppPersonRequest;
use App\Modules\Persons\Http\Resources\PersonResource;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/persons` — the contact list both apps work from.
 *
 * Scoping is by role and happens server-side: a telecaller sees the persons
 * in the regions they cover, an MR sees the ones assigned to them. The app
 * never sends a region filter it could tamper with.
 *
 * For a telecaller the list doubles as the call queue, so each row carries
 * the last call *they* made on that person. That comes from the Calls module
 * through its service contract, in one query for the whole page.
 *
 * `?mine=1` switches the clamp from territory to ownership — "My clients",
 * the ones the caller is an assignee on, wherever they sit. `store()` is the
 * other half: either app adds a client, names its counterpart, and the
 * caller's own side comes from the token.
 */
class PersonController extends Controller
{
    public function __construct(
        private readonly PersonServiceInterface $persons,
        private readonly CallLogServiceInterface $calls,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'type' => $request->input('type'),
            'region_id' => $request->input('region_id'),
            // Active only: the apps work a live list, and a deactivated
            // person is not someone to call on today.
            'status' => 'active',
        ];

        // An MR works their own assignments, not their whole region.
        if ($user->isMr()) {
            $filters['assigned_mr_id'] = $user->id;
        }

        // "My clients" asks a different question from the call queue: the
        // rows I own, wherever they sit, rather than the rows in territory I
        // cover. Only `?mine=1` gets that clamp — the unflagged call keeps
        // the territory scope the shipped builds already rely on.
        $page = $request->boolean('mine')
            ? $this->persons->paginateAssignedTo($user, $filters, PerPage::from($request))
            : $this->persons->paginateForUser($user, $filters, PerPage::from($request));

        $lastCalls = $user->isTelecaller()
            ? $this->calls->lastCallsFor($user, collect($page->items())->pluck('id')->all())
            : collect();

        return ApiResponse::paginated(
            $page->through(fn ($person) => (new PersonResource($person))
                ->withLastCall(
                    ($call = $lastCalls->get($person->id)) !== null
                        ? (new CallLogResource($call))->resolve()
                        : null,
                )
                ->resolve()),
            'Persons loaded.',
        );
    }

    /**
     * Add Client. Both apps post here; what differs is which counterpart the
     * body names, which `StoreAppPersonRequest` decides from the token's
     * role and `PersonService::createForApp` completes.
     *
     * The created row comes back in full, relations loaded, so the app can
     * drop it straight into My Clients without a second request.
     */
    public function store(StoreAppPersonRequest $request): JsonResponse
    {
        $person = $this->persons->createForApp($request->user(), $request->payload());

        return ApiResponse::success(
            new PersonResource($person->load(['clientType', 'region', 'assignedMr', 'assignedTelecaller'])),
            'Client added.',
            201,
        );
    }

    /**
     * Edit Client. A client the caller is an assignee on — the rows My
     * Clients shows — and anyone else's is a **404**, the same way an
     * amendment to another rep's visit is.
     *
     * A PATCH: the app sends what the rep changed. What the body may not
     * carry is what it may not carry on `store()`, plus the caller's own
     * assignment, which would move the client off the list it was edited
     * from (`UpdateAppPersonRequest`).
     */
    public function update(UpdateAppPersonRequest $request, int|string $person): JsonResponse
    {
        $updated = $this->persons->updateForApp($request->user(), $person, $request->payload());

        return ApiResponse::success(
            new PersonResource($updated->load(['clientType', 'region', 'assignedMr', 'assignedTelecaller'])),
            'Client updated.',
        );
    }
}
