<?php

namespace App\Modules\Messages\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Persistence for messages.
 */
interface MessageRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /**
     * How many unread messages one recipient has. This is the endpoint the MR
     * app polls, so it stays a single indexed COUNT and nothing more.
     */
    public function unreadCountFor(int $recipientId): int;

    /** Totals for the panel header. */
    public function summary(array $filters): array;

    /** Stamp read_at on one recipient's unread messages; returns how many. */
    public function markRead(int $recipientId, ?int $messageId = null): int;
}
