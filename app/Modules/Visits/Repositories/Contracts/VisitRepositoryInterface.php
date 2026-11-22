<?php

namespace App\Modules\Visits\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Modules\Visits\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Persistence for visits.
 */
interface VisitRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /** Totals for the report header, plus a breakdown by purpose. */
    public function summary(array $filters): array;

    /** Visits per day, `YYYY-MM-DD => count`, days with rows only. */
    public function trend(array $filters): array;

    /** Busiest reps first, `mr name => count`. */
    public function countsByMr(array $filters, int $limit = 10): array;

    /**
     * Per-rep visit figures for the range, `mr_id => {visits, persons,
     * geotagged, active_days}`.
     *
     * @return array<int, array{visits: int, persons: int, geotagged: int, active_days: int}>
     */
    public function perMrSummary(array $filters): array;

    /**
     * Visits per region, `region_id => count`, via the visited person.
     *
     * @return array<int, int>
     */
    public function countsByRegion(array $filters): array;

    /**
     * Newest visit per person, keyed by `person_id`.
     *
     * @param  array<int, int>  $personIds
     * @return Collection<int, Visit>
     */
    public function lastVisitPerPerson(array $personIds): Collection;
}
