<?php

namespace App\Modules\Calls\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Modules\Calls\Models\CallLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Persistence for call logs.
 */
interface CallLogRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /** Totals for the report header, plus a breakdown by status. */
    public function summary(array $filters): array;

    /** Calls per day, `YYYY-MM-DD => count`, days with rows only. */
    public function trend(array $filters): array;

    /**
     * Per-telecaller call figures, `telecaller_id => {calls, persons,
     * converted, active_days}`.
     *
     * @return array<int, array{calls: int, persons: int, converted: int, active_days: int}>
     */
    public function perTelecallerSummary(array $filters): array;

    /**
     * Per-telecaller status mix, `telecaller_id => [status => count]`.
     *
     * @return array<int, array<string, int>>
     */
    public function statusMixByTelecaller(array $filters): array;

    /**
     * Promised call-backs against those actually made, per telecaller.
     *
     * @return array<int, array{promised: int, honoured: int, on_time: int, overdue_open: int}>
     */
    public function followUpCompliance(array $filters): array;

    /**
     * Calls per region, `region_id => count`, via the person called.
     *
     * @return array<int, int>
     */
    public function countsByRegion(array $filters): array;

    /**
     * The most recent call on each of these persons, keyed by person id.
     * One query, so a person list can show "last outcome" without N+1.
     *
     * @param  array<int, int>  $personIds
     * @param  int|null  $telecallerId  limit to one caller's own history
     * @return Collection<int, CallLog>
     */
    public function lastCallPerPerson(array $personIds, ?int $telecallerId = null): Collection;

    /** Open follow-ups for one telecaller, soonest first. */
    public function followUps(int $telecallerId, bool $dueOnly, int $perPage): LengthAwarePaginator;

    /**
     * Close any follow-up still open on this person for this telecaller.
     * Called when a newer call is logged, so the queue cannot keep showing a
     * call-back that has already happened.
     */
    public function closeOpenFollowUps(int $telecallerId, int $personId, ?int $exceptId = null): int;
}
