<?php

namespace App\Modules\Visits\Services\Contracts;

use App\Modules\Users\Models\User;
use App\Modules\Visits\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Visit entry and reporting.
 */
interface VisitServiceInterface
{
    /** Record a visit for this MR. The MR is the caller, never the payload. */
    public function createFor(User $mr, array $data): Visit;

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
}
