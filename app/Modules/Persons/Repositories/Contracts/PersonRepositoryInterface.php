<?php

namespace App\Modules\Persons\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Persistence for persons. `applyFilters` carries the whole filter vocabulary
 * the panel and (from Phase 6) the apps use: type, region, assignee, status,
 * free-text search.
 */
interface PersonRepositoryInterface extends RepositoryInterface
{
    /** Paginate with relations eager-loaded, so a list is a fixed query count. */
    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /** How many persons sit in each of these regions, keyed by region id. */
    /** How many rows match, without pulling a page of them. */
    public function countMatching(array $filters): int;

    /**
     * Active persons assigned to each rep, `assigned_mr_id => count`: the
     * denominator of the MR report's coverage figure.
     *
     * @return array<int, int>
     */
    public function activeCountsByMr(array $filters = []): array;

    public function countsByRegion(array $regionIds): array;
}
