<?php

namespace App\Modules\Calls\Services\Contracts;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Call logging and reporting.
 *
 * The *ForUser methods are the scoped entry points. A non-admin sees only
 * their own calls, so the clamp lives here rather than in a controller that
 * could forget it.
 */
interface CallLogServiceInterface
{
    /** Log a call for this telecaller. The caller comes from the token. */
    public function logFor(User $telecaller, array $data): CallLog;

    /** Amend one of this telecaller's own calls (notes, status, follow-up). */
    public function updateFor(User $telecaller, int|string $id, array $data): CallLog;

    /** Mark a promised call-back as handled without logging a new call. */
    public function completeFollowUp(User $telecaller, int|string $id): CallLog;

    public function find(int|string $id): CallLog;

    /** The same row, refused as missing when the user may not see it. */
    public function findForUser(User $user, int|string $id): CallLog;

    public function delete(int|string $id): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** The panel list, clamped: an admin sees all, anyone else their own. */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** One telecaller's own history, for the app. */
    public function historyFor(User $telecaller, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** Open call-backs for this telecaller, soonest first. */
    public function followUpsFor(User $telecaller, bool $dueOnly = true, int $perPage = 20): LengthAwarePaginator;

    public function summary(array $filters = []): array;

    /** The same counters, clamped the same way as paginateForUser. */
    public function summaryForUser(User $user, array $filters = []): array;

    /**
     * Calls per day, `YYYY-MM-DD => count`, clamped to this caller (or every
     * call, for an admin). Days with no rows are absent.
     */
    public function trendForUser(User $user, array $filters = []): array;

    /**
     * Last outcome per person, keyed by person id — what a call queue shows
     * next to each name. Scoped to this user's own calls unless they are an
     * admin, so one telecaller's notes do not surface in another's queue.
     *
     * @param  array<int, int>  $personIds
     * @return Collection<int, CallLog>
     */
    public function lastCallsFor(User $user, array $personIds): Collection;
}
