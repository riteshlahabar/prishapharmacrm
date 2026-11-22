<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Exceptions\AttendanceConflict;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Attendance\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Modules\Attendance\Services\Contracts\AttendanceServiceInterface;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Who is on duty, and when.
 *
 * Two rules matter and both live here. The server sets every timestamp, so a
 * device with a wound-back clock cannot forge a shift. And the day's sequence
 * is enforced — check in once, check out once, in that order — so a reinstall
 * or a double tap cannot reopen a finished day.
 */
class AttendanceService implements AttendanceServiceInterface
{
    public function __construct(private readonly AttendanceRepositoryInterface $attendances) {}

    public function checkIn(User $mr, ?float $lat, ?float $lng): Attendance
    {
        $today = $this->todayDate();

        if ($this->attendances->forDate($mr->id, $today)?->isCheckedIn()) {
            throw AttendanceConflict::alreadyCheckedIn();
        }

        try {
            $attendance = $this->attendances->firstOrCreateForDate($mr->id, $today);
        } catch (UniqueConstraintViolationException) {
            // Two requests raced; the row the other one made is the real one.
            throw AttendanceConflict::alreadyCheckedIn();
        }

        // Re-read after the insert: the loser of a race must not overwrite.
        if ($attendance->isCheckedIn()) {
            throw AttendanceConflict::alreadyCheckedIn();
        }

        $attendance->forceFill([
            'check_in_at' => now(),
            'check_in_lat' => $lat,
            'check_in_lng' => $lng,
        ])->save();

        return $attendance->refresh();
    }

    public function checkOut(User $mr, ?float $lat, ?float $lng): Attendance
    {
        $attendance = $this->attendances->forDate($mr->id, $this->todayDate());

        if ($attendance === null || ! $attendance->isCheckedIn()) {
            throw AttendanceConflict::notCheckedIn();
        }

        if ($attendance->isCheckedOut()) {
            throw AttendanceConflict::alreadyCheckedOut();
        }

        $attendance->forceFill([
            'check_out_at' => now(),
            'check_out_lat' => $lat,
            'check_out_lng' => $lng,
        ])->save();

        return $attendance->refresh();
    }

    public function today(User $mr): ?Attendance
    {
        return $this->attendances->forDate($mr->id, $this->todayDate());
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->attendances->paginateWithRelations($perPage, $filters);
    }

    public function summary(array $filters = []): array
    {
        return $this->attendances->summary($filters);
    }

    public function historyFor(User $mr, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate(array_merge($filters, ['mr_id' => $mr->id]), $perPage);
    }

    /**
     * The working day in the application's own timezone. Rows are stored UTC,
     * but "today" has to mean the MR's calendar day, not UTC's.
     */
    private function todayDate(): string
    {
        return now()->toDateString();
    }
}
