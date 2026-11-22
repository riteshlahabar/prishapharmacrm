<?php

namespace App\Modules\Reports\Services\Contracts;

use App\Modules\Users\Models\User;

/**
 * The six management reports. Every method takes the viewer and returns rows
 * already clamped to what that viewer may see — there is no unclamped form,
 * so a controller cannot forget (the Phase 8 attendance bug, which was exactly
 * a report reading the unclamped method).
 *
 * Each returns `['rows' => [...], 'totals' => [...]]`: the same array the
 * Blade renders and the CSV writes, so the download can never disagree with
 * the screen.
 */
interface ReportServiceInterface
{
    /**
     * Normalises a request's date range. Defaults to the last 30 days, which
     * is the period a field manager actually reviews.
     *
     * @return array{date_from: string, date_to: string, days: int}
     */
    public function period(?string $from, ?string $to): array;

    /** One row per medical rep: presence, visits, coverage. */
    public function mrPerformance(User $viewer, array $filters): array;

    /** One row per telecaller: calls, conversions, follow-ups, messages. */
    public function telecallerPerformance(User $viewer, array $filters): array;

    /** One row per person not visited / not called recently enough. */
    public function coverage(User $viewer, array $filters): array;

    /** One row per region: headcount, contacts, visits, calls. */
    public function regionRollup(User $viewer, array $filters): array;

    /** Reps down the side, days across the top, P/O/A per cell. */
    public function attendanceRegister(User $viewer, array $filters): array;

    /** Promised call-backs against those actually made, per telecaller. */
    public function followUpCompliance(User $viewer, array $filters): array;
}
