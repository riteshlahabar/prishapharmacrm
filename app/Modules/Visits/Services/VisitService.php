<?php

namespace App\Modules\Visits\Services;

use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Models\User;
use App\Modules\Visits\Enums\VisitPurpose;
use App\Modules\Visits\Models\Visit;
use App\Modules\Visits\Repositories\Contracts\VisitRepositoryInterface;
use App\Modules\Visits\Services\Contracts\VisitServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class VisitService implements VisitServiceInterface
{
    public function __construct(
        private readonly VisitRepositoryInterface $visits,
        private readonly RegionAssignmentServiceInterface $assignment,
    ) {}

    /**
     * The MR comes from the authenticated caller, never from the request body:
     * an app build with a tampered payload must not be able to file a visit
     * against somebody else's name.
     */
    public function createFor(User $mr, array $data): Visit
    {
        /** @var Visit */
        return $this->visits->create([
            'mr_id' => $mr->id,
            'person_id' => $data['person_id'],
            'visit_date' => $data['visit_date'] ?? now()->toDateString(),
            'purpose' => $data['purpose'] instanceof VisitPurpose
                ? $data['purpose']
                : VisitPurpose::from((string) $data['purpose']),
            'in_time' => $data['in_time'] ?? null,
            'out_time' => $data['out_time'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            // When the device read the GPS. Absent if it sent coordinates
            // without saying when, or sent none at all.
            'location_fetched_at' => isset($data['latitude'], $data['longitude'])
                ? ($data['location_fetched_at'] ?? now())
                : null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function find(int|string $id): Visit
    {
        /** @var Visit */
        return $this->visits->findOrFail($id);
    }

    /**
     * A visit a non-admin has no territory for is reported as missing, not as
     * forbidden: confirming the row exists would already leak that an MR
     * called on that person.
     */
    public function findForUser(User $user, int|string $id): Visit
    {
        $visit = $this->find($id);

        // The rule itself lives in VisitPolicy, so the panel and the API
        // cannot drift apart; what differs is only how a denial is reported.
        if (! Gate::forUser($user)->allows('view', $visit)) {
            throw new ModelNotFoundException;
        }

        return $visit;
    }

    public function delete(int|string $id): bool
    {
        return $this->visits->delete($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->visits->paginateWithRelations($perPage, $filters);
    }

    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate($this->clampToTerritory($user, $filters), $perPage);
    }

    public function historyFor(User $mr, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate(array_merge($filters, ['mr_id' => $mr->id]), $perPage);
    }

    public function summary(array $filters = []): array
    {
        return $this->visits->summary($filters);
    }

    /**
     * Visits per day for whatever this user may see. Only the clamped form is
     * exposed: a dashboard always renders for somebody, and an unclamped
     * trend is the kind of thing a caller forgets to scope.
     */
    public function trendForUser(User $user, array $filters = []): array
    {
        return $this->visits->trend($this->clampToTerritory($user, $filters));
    }

    /** Busiest reps first, within what this user may see. */
    public function countsByMrForUser(User $user, array $filters = [], int $limit = 10): array
    {
        return $this->visits->countsByMr($this->clampToTerritory($user, $filters), $limit);
    }

    /** Per-rep visit figures for the Reports module, clamped to this viewer. */
    public function perMrSummaryForUser(User $user, array $filters = []): array
    {
        return $this->visits->perMrSummary($this->clampToTerritory($user, $filters));
    }

    /** Visits per region, clamped to this viewer. */
    public function countsByRegionForUser(User $user, array $filters = []): array
    {
        return $this->visits->countsByRegion($this->clampToTerritory($user, $filters));
    }

    /**
     * Newest visit per person, keyed by `person_id`. The caller has already
     * had the person list clamped, so the ids coming in are in scope.
     */
    public function lastVisitsFor(array $personIds): Collection
    {
        return $this->visits->lastVisitPerPerson($personIds);
    }

    public function summaryForUser(User $user, array $filters = []): array
    {
        return $this->summary($this->clampToTerritory($user, $filters));
    }

    /**
     * An admin sees everything; anyone else is pinned to their own regions.
     * An empty region list stays empty on purpose — the repository turns that
     * into "no rows", which is the right answer for a telecaller who covers
     * nothing yet.
     */
    private function clampToTerritory(User $user, array $filters): array
    {
        if (! $user->isAdmin()) {
            $filters['region_scope'] = $this->assignment->idsFor($user);
        }

        return $filters;
    }
}
