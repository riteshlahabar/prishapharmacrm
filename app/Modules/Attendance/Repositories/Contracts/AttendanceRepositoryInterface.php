<?php

namespace App\Modules\Attendance\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Modules\Attendance\Models\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Persistence for presence rows.
 */
interface AttendanceRepositoryInterface extends RepositoryInterface
{
    /** Today's row for this MR, or null if they have not checked in. */
    public function forDate(int $mrId, string $date): ?Attendance;

    /**
     * Create the day's row, or return the existing one — the unique index on
     * (mr_id, date) is the backstop against a racing double check-in.
     */
    public function firstOrCreateForDate(int $mrId, string $date): Attendance;

    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /** Totals for the report header: present, on duty, completed. */
    public function summary(array $filters): array;

    /** Reps present per day, `YYYY-MM-DD => count`, days with rows only. */
    public function trend(array $filters): array;

    /**
     * Per-rep presence for the range, `mr_id => {days_present, days_completed,
     * days_open, minutes}` — what the MR report and the register are built on.
     *
     * @return array<int, array{days_present: int, days_completed: int, days_open: int, minutes: int}>
     */
    public function perMrSummary(array $filters): array;

    /**
     * `mr_id => ['YYYY-MM-DD' => 'P'|'O'|'A']`. Days with no row are absent
     * and left out; the caller knows the range and fills them.
     *
     * @return array<int, array<string, string>>
     */
    public function registerGrid(array $filters): array;
}
