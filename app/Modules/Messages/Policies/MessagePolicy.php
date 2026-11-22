<?php

namespace App\Modules\Messages\Policies;

use App\Modules\Messages\Models\Message;
use App\Modules\Users\Models\User;

/**
 * Who may touch a message. Scope is **either party** — sender or recipient —
 * which is also what the panel shows. Messaging is one-directional in Phase
 * 5: a telecaller writes, an MR reads.
 *
 * The API renders a row denial as 404, not 403 — see VisitPolicy.
 */
class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canSignIn()
            && ($user->isMr() || $user->isTelecaller() || $user->isAdmin() || $user->can('messages.view'));
    }

    public function view(User $user, Message $message): bool
    {
        return $user->canSignIn() && ($user->isAdmin() || $this->isParty($user, $message));
    }

    /** Sending is the telecaller's half. */
    public function create(User $user): bool
    {
        return $user->canSignIn() && ($user->isTelecaller() || $user->can('messages.create'));
    }

    /**
     * Only the addressee marks a message read — not the sender, and not an
     * admin reading it in the panel, either of whom would otherwise clear the
     * recipient's unread badge for them.
     */
    public function markRead(User $user, Message $message): bool
    {
        return $user->canSignIn() && (int) $message->to_user_id === (int) $user->id;
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->canSignIn() && $user->can('messages.delete');
    }

    private function isParty(User $user, Message $message): bool
    {
        return (int) $message->from_user_id === (int) $user->id
            || (int) $message->to_user_id === (int) $user->id;
    }
}
