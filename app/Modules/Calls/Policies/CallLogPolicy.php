<?php

namespace App\Modules\Calls\Policies;

use App\Modules\Calls\Models\CallLog;
use App\Modules\Users\Models\User;

/**
 * Who may touch a call log. Unlike a visit, the scope is the **caller**, not
 * the territory: a telecaller's notes are their own working record and are
 * not shared with whoever else covers the region.
 *
 * Note the deliberate asymmetry between `view` and `update`: an admin reads
 * any call for reporting, but does not rewrite someone else's notes.
 *
 * The API renders a row denial as 404, not 403 — see VisitPolicy.
 */
class CallLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canSignIn()
            && ($user->isTelecaller() || $user->isAdmin() || $user->can('calls.view'));
    }

    public function view(User $user, CallLog $call): bool
    {
        return $user->canSignIn() && ($user->isAdmin() || $this->owns($user, $call));
    }

    public function create(User $user): bool
    {
        return $user->canSignIn() && ($user->isTelecaller() || $user->can('calls.create'));
    }

    /** The caller's own record: no admin bypass, by design. */
    public function update(User $user, CallLog $call): bool
    {
        return $user->canSignIn() && $this->owns($user, $call);
    }

    public function delete(User $user, CallLog $call): bool
    {
        return $user->canSignIn()
            && $user->can('calls.delete')
            && ($user->isAdmin() || $this->owns($user, $call));
    }

    private function owns(User $user, CallLog $call): bool
    {
        return (int) $call->telecaller_id === (int) $user->id;
    }
}
