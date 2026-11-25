<?php

namespace App\Modules\Visits\Services\Contracts;

use App\Modules\Users\Models\User;
use App\Modules\Visits\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Visit entry and reporting.
 */
interface VisitServiceInterface
{
    /** Record a visit for this MR. The MR is the caller, never the payload. */
    public function createFor(User $mr, array $data): Visit;

    /**
     * Amend a visit the rep filed themselves — purpose, clocks, notes and
     * product lines. Only the keys present in `$data` are written. Anyone
     * but the author gets a 404, and the person, coordinates and photo are
     * never amendable (see UpdateVisitRequest).
     */
    public function updateFor(User $mr, int|string $id, array $data): Visit;

    public function find(int|string $id): Visit;

    /**
     * The same row, but refused (404) when the user does not cover the
     * person's region. `find` alone would let a telecaller read any visit by
     * guessing its id, which is exactly what the scoped list prevents.
     */
    public function findForUser(User $user, int|string $id): Visit;

    public function delete(int|string $id): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** The same list, clamped to the regions a non-admin covers. */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** An MR's own history, for the app. */
    public function historyFor(User $mr, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function summary(array $filters = []): array;

    /** The same counters, clamped to the regions a non-admin covers. */
    public function summaryForUser(User $user, array $filters = []): array;

    /**
     * Visits per day, `YYYY-MM-DD => count`, clamped to this user's regions.
     * Days with no rows are absent — the caller fills the range.
     */
    public function trendForUser(User $user, array $filters = []): array;

    /** Busiest reps first, `mr name => count`, clamped to this user. */
    public function countsByMrForUser(User $user, array $filters = [], int $limit = 10): array;

    /**
     * Per-rep visit figures for the range, clamped to this viewer:
     * `mr_id => {visits, persons, geotagged, active_days}`.
     *
     * @return array<int, array<string, int>>
     */
    public function perMrSummaryForUser(User $user, array $filters = []): array;

    /**
     * Visits per region, clamped to this viewer: `region_id => count`.
     *
     * @return array<int, int>
     */
    public function countsByRegionForUser(User $user, array $filters = []): array;

    /**
     * Newest visit per person, keyed by `person_id`, for an already-scoped
     * set of person ids.
     *
     * @param  array<int, int>  $personIds
     * @return Collection<int, Visit>
     */
    public function lastVisitsFor(array $personIds): Collection;
}
