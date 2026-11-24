<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Http\Resources\AssigneeResource;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * `/api/v1/assignees/*` — the two "who may own this client?" pickers the Add
 * Client forms show (docs/api/v1-{mr,telecaller}.md).
 *
 * Every *active* user of the counterpart role, unscoped by territory. That is
 * deliberate: cross-territory assignment is the whole point of the feature —
 * a telecaller working several regions hands a client to whichever MR covers
 * the doctor — and the field is required, so a region filter that came back
 * empty would make the client impossible to add at all. Each row carries its
 * regions, so the person choosing still sees who works where.
 *
 * Separate from `/mrs`, which answers which MRs this telecaller may *message*
 * and is region-scoped for that reason.
 */
class AssigneeController extends Controller
{
    public function __construct(private readonly UserServiceInterface $users) {}

    /** For the telecaller app: which MR to put the new client under. */
    public function mrs(): JsonResponse
    {
        return ApiResponse::success(
            $this->picker(UserRole::Mr),
            'Medical reps loaded.',
        );
    }

    /** For the MR app: which telecaller to put the new client under. */
    public function telecallers(): JsonResponse
    {
        return ApiResponse::success(
            $this->picker(UserRole::Telecaller),
            'Telecallers loaded.',
        );
    }

    /**
     * A plain array, no `meta` — a small picker, like `/mrs`
     * (docs/api/v1-common.md).
     *
     * Both region relations are eager-loaded because `covered_regions` reads
     * whichever one the role uses, and a picker must not turn into N+1.
     *
     * @return array<int, array<string, mixed>>
     */
    private function picker(UserRole $role): array
    {
        $people = $this->users->activeOfRole($role)->load(['region', 'regions']);

        return AssigneeResource::collection($people)->resolve();
    }
}
