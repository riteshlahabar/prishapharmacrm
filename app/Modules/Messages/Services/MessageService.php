<?php

namespace App\Modules\Messages\Services;

use App\Modules\Messages\Models\Message;
use App\Modules\Messages\Repositories\Contracts\MessageRepositoryInterface;
use App\Modules\Messages\Services\Contracts\MessageServiceInterface;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Who may tell whom what.
 *
 * Everything this module needs about people and territories comes through
 * other modules' service contracts (docs/conventions.md); it owns `messages`
 * and nothing else.
 */
class MessageService implements MessageServiceInterface
{
    public function __construct(
        private readonly MessageRepositoryInterface $messages,
        private readonly UserServiceInterface $users,
        private readonly PersonServiceInterface $persons,
        private readonly RegionAssignmentServiceInterface $assignment,
    ) {}

    public function sendAs(User $sender, array $data): Message
    {
        $personId = $data['person_id'] ?? null;

        // Re-checked here, not only in the request: the rule is the module's,
        // and a future caller must not be able to route around it.
        if (! $this->canSendTo($sender, $data['to_user_id'], $personId)) {
            throw new ModelNotFoundException;
        }

        /** @var Message */
        return $this->messages->create([
            'from_user_id' => $sender->id,
            'to_user_id' => $data['to_user_id'],
            'person_id' => $personId,
            'call_log_id' => $data['call_log_id'] ?? null,
            'subject' => $data['subject'],
            'body' => $data['body'],
            // Never read: a message starts unread by definition, and letting
            // the sender set that would let them mark their own message read.
            'read_at' => null,
        ]);
    }

    public function recipientsFor(User $sender, int|string|null $personId = null): Collection
    {
        $recipients = $this->users->activeOfRoleInRegions(
            UserRole::Mr,
            $this->assignment->idsFor($sender),
        );

        /**
         * The person's own MR, even from another region. The common case is
         * "I spoke to this doctor, their rep should visit" — refusing that
         * because of a region mismatch in the data would be a dead end.
         */
        if ($personId !== null && ($assigned = $this->assignedMrFor($personId)) !== null) {
            if (! $recipients->contains('id', $assigned->id)) {
                $recipients = $recipients->push($assigned)->sortBy('name')->values();
            }
        }

        return $recipients;
    }

    public function canSendTo(User $sender, int|string $recipientId, int|string|null $personId = null): bool
    {
        return $this->recipientsFor($sender, $personId)->contains('id', (int) $recipientId);
    }

    public function inboxFor(User $recipient, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate(array_merge($filters, ['to_user_id' => $recipient->id]), $perPage);
    }

    public function unreadCountFor(User $recipient): int
    {
        return $this->messages->unreadCountFor((int) $recipient->id);
    }

    public function markRead(User $recipient, int|string $id): Message
    {
        $message = $this->find($id);

        // Only the addressee marks it read — not the sender, not an admin.
        // Missing, not forbidden (MessagePolicy::markRead).
        if (! Gate::forUser($recipient)->allows('markRead', $message)) {
            throw new ModelNotFoundException;
        }

        // Already read: leave the original timestamp alone and say yes, so a
        // retry from a flaky connection is not an error.
        if ($message->isRead()) {
            return $message;
        }

        $this->messages->markRead((int) $recipient->id, (int) $message->id);

        return $message->refresh();
    }

    public function markAllRead(User $recipient): int
    {
        return $this->messages->markRead((int) $recipient->id);
    }

    public function find(int|string $id): Message
    {
        /** @var Message */
        return $this->messages->findOrFail($id);
    }

    public function findForUser(User $user, int|string $id): Message
    {
        $message = $this->find($id);

        // Either party, or an admin — the rule is MessagePolicy's; this layer
        // only chooses that a denial reads as missing rather than forbidden.
        if (! Gate::forUser($user)->allows('view', $message)) {
            throw new ModelNotFoundException;
        }

        return $message;
    }

    public function delete(int|string $id): bool
    {
        return $this->messages->delete($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->messages->paginateWithRelations($perPage, $filters);
    }

    public function paginateForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate($this->clampToOwn($user, $filters), $perPage);
    }

    public function summary(array $filters = []): array
    {
        return $this->messages->summary($filters);
    }

    public function summaryForUser(User $user, array $filters = []): array
    {
        return $this->summary($this->clampToOwn($user, $filters));
    }

    /** A message has two owners: a non-admin sees their own, either way round. */
    private function clampToOwn(User $user, array $filters): array
    {
        if (! $user->isAdmin()) {
            $filters['participant_id'] = $user->id;
        }

        return $filters;
    }

    private function assignedMrFor(int|string $personId): ?User
    {
        try {
            $person = $this->persons->find($personId);
        } catch (ModelNotFoundException) {
            return null;
        }

        $mr = $person->assignedMr;

        return $mr !== null && $mr->is_active ? $mr : null;
    }
}
