<?php

namespace App\Modules\Attendance\Services\Contracts;

use App\Modules\Attendance\Exceptions\AttendanceConflict;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Presence rules. The device supplies coordinates; this decides whether the
 * action is allowed and stamps the time itself.
 *
 * @throws AttendanceConflict
 */
interface AttendanceServiceInterface
{
    public function checkIn(User $mr, ?float $lat, ?float $lng): Attendance;

    public function checkOut(User $mr, ?float $lat, ?float $lng): Attendance;

    /** Today's row, or null — drives the MR app's button state. */
    public function today(User $mr): ?Attendance;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function summary(array $filters = []): array;

    /**
     * The panel report, clamped to the regions this user covers. The panel
     * calls this rather than `paginate()` so a telecaller granted
     * `attendance.view` cannot read every rep in the company — the same
     * contract the Visits, Calls, Messages and Persons modules keep.
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** Totals clamped to the regions this user covers. */
    public function summaryForUser(User $user, array $filters = []): array;

    /**
     * Reps present per day, `YYYY-MM-DD => count`, clamped to this user.
     * Days with no check-in are absent — the caller fills the range.
     */
    public function trendForUser(User $user, array $filters = []): array;

    /** Own history for the MR app. */
    public function historyFor(User $mr, array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
