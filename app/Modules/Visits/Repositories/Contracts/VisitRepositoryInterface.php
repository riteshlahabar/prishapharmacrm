<?php

namespace App\Modules\Visits\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
