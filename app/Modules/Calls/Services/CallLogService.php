<?php

namespace App\Modules\Calls\Services;

use App\Modules\Calls\Enums\CallStatus;
use App\Modules\Calls\Models\CallLog;
use App\Modules\Calls\Repositories\Contracts\CallLogRepositoryInterface;
use App\Modules\Calls\Services\Contracts\CallLogServiceInterface;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * What a telecaller did, and what they promised to do next.
 *
 * Two rules live here. `called_at` is server-set, like the attendance stamps,
 * so a device cannot back-date work. And logging a call closes any call-back
 * still open on that person, so the follow-up queue never asks for a call
 * that has already been made.
 */
class CallLogService implements CallLogServiceInterface
{
    public function __construct(private readonly CallLogRepositoryInterface $calls) {}

    public function logFor(User $telecaller, array $data): CallLog
    {
        $status = $this->status($data['status']);

        /** @var CallLog $call */
        $call = $this->calls->create([
            'telecaller_id' => $telecaller->id,
            'person_id' => $data['person_id'],
            // Server time: the app sends an outcome, never a clock.
            'called_at' => now(),
            'status' => $status,
            'notes' => $data['notes'] ?? null,
            // A follow-up date is only meaningful on a status that asks for
            // one; anything else carrying a date would sit in the queue for
            // ever, since nothing would come along to close it.
            'follow_up_at' => $status->needsFollowUp() ? ($data['follow_up_at'] ?? null) : null,
        ]);

        $this->calls->closeOpenFollowUps($telecaller->id, (int) $data['person_id'], $call->id);

        return $call->refresh();
    }

    public function updateFor(User $telecaller, int|string $id, array $data): CallLog
    {
        $call = $this->ownedBy($telecaller, $id);

        $status = array_key_exists('status', $data) && $data['status'] !== null
            ? $this->status($data['status'])
            : $call->status;

        $attributes = ['status' => $status];

        if (array_key_exists('notes', $data)) {
            $attributes['notes'] = $data['notes'];
        }

        // Dropping out of a call-back status retires the promise with it.
        if ($status->needsFollowUp()) {
            if (array_key_exists('follow_up_at', $data)) {
                $attributes['follow_up_at'] = $data['follow_up_at'];
                $attributes['follow_up_done_at'] = null;
            }
        } else {
            $attributes['follow_up_at'] = null;
            $attributes['follow_up_done_at'] = null;
        }

        /** @var CallLog */
        return $this->calls->update($call->id, $attributes);
    }

    public function completeFollowUp(User $telecaller, int|string $id): CallLog
    {
        $call = $this->ownedBy($telecaller, $id);

        if (! $call->hasOpenFollowUp()) {
            // Nothing to close: idempotent rather than an error, so a retry
            // from a flaky connection does not surface as a failure.
            return $call;
        }

        /** @var CallLog */
        return $this->calls->update($call->id, ['follow_up_done_at' => now()]);
    }

    public function find(int|string $id): CallLog
    {
        /** @var CallLog */
        return $this->calls->findOrFail($id);
    }

    public function findForUser(User $user, int|string $id): CallLog
    {
        $call = $this->find($id);

        if (! $user->isAdmin() && (int) $call->telecaller_id !== (int) $user->id) {
            // Missing, not forbidden: a 403 would confirm the row exists.
            throw new ModelNotFoundException;
        }

        return $call;
    }

    public function delete(int|string $id): bool
    {
        return $this->calls->delete($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->calls->paginateWithRelations($perPage, $filters);
    }

    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate($this->clampToOwn($user, $filters), $perPage);
    }

    public function historyFor(User $telecaller, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate(array_merge($filters, ['telecaller_id' => $telecaller->id]), $perPage);
    }

    public function followUpsFor(User $telecaller, bool $dueOnly = true, int $perPage = 20): LengthAwarePaginator
    {
        return $this->calls->followUps($telecaller->id, $dueOnly, $perPage);
    }

    public function summary(array $filters = []): array
    {
        return $this->calls->summary($filters);
    }

    public function summaryForUser(User $user, array $filters = []): array
    {
        return $this->summary($this->clampToOwn($user, $filters));
    }

    public function lastCallsFor(User $user, array $personIds): Collection
    {
        return $this->calls->lastCallPerPerson(
            $personIds,
            $user->isAdmin() ? null : (int) $user->id,
        );
    }

    /**
     * A call belongs to the telecaller who made it. Unlike visits, the scope
     * is the caller, not the territory: a telecaller's notes are their own
     * working record, not shared with whoever else covers the region.
     */
    private function clampToOwn(User $user, array $filters): array
    {
        if (! $user->isAdmin()) {
            $filters['telecaller_id'] = $user->id;
        }

        return $filters;
    }

    private function ownedBy(User $telecaller, int|string $id): CallLog
    {
        $call = $this->find($id);

        if ((int) $call->telecaller_id !== (int) $telecaller->id) {
            throw new ModelNotFoundException;
        }

        return $call;
    }

    private function status(CallStatus|string $status): CallStatus
    {
        return $status instanceof CallStatus ? $status : CallStatus::from($status);
    }
}
