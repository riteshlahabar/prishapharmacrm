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
}
