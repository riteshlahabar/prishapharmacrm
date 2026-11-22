<?php

namespace App\Modules\Messages\Services\Contracts;

use App\Modules\Messages\Models\Message;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * In-app messaging, telecaller → MR. No Firebase: the MR app polls.
 */
interface MessageServiceInterface
{
    /** Send as this user. The sender comes from the token, never the body. */
    public function sendAs(User $sender, array $data): Message;

    /**
     * MRs this telecaller may write to: everyone working a region they cover,
     * plus the MR assigned to the person being written about, who might sit
     * elsewhere. Used by the recipient picker *and* by validation, so the two
     * cannot drift apart.
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(User $sender, int|string|null $personId = null): Collection;

    /** Whether that recipient is one of the above. */
    public function canSendTo(User $sender, int|string $recipientId, int|string|null $personId = null): bool;

    /** One recipient's inbox, newest first. */
    public function inboxFor(User $recipient, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** What the MR app polls. */
    public function unreadCountFor(User $recipient): int;

    /** Mark one message read. Idempotent, and only by its recipient. */
    public function markRead(User $recipient, int|string $id): Message;

    /** Mark the whole inbox read; returns how many changed. */
    public function markAllRead(User $recipient): int;

    public function find(int|string $id): Message;

    /** The same row, refused as missing when the user is not a party to it. */
    public function findForUser(User $user, int|string $id): Message;

    public function delete(int|string $id): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** The panel list: an admin sees all, anyone else only their own traffic. */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function summary(array $filters = []): array;

    /** The same counters, clamped the same way as paginateForUser. */
    public function summaryForUser(User $user, array $filters = []): array;

    /**
     * Messages sent per sender in the range, clamped to this viewer:
     * `from_user_id => count`.
     *
     * @return array<int, int>
     */
    public function sentCountsForUser(User $user, array $filters = []): array;
}
