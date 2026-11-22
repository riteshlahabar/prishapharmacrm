<?php

namespace App\Modules\Persons\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Calls\Http\Resources\CallLogResource;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;
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

        $page = $this->persons->paginateForUser($user, $filters, (int) $request->integer('per_page', 20));

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
}
