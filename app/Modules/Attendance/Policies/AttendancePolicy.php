<?php

namespace App\Modules\Attendance\Policies;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\Users\Models\User;

/**
 * Who may touch an attendance row. Presence is the rep's own: an MR punches
 * and reads their own days, an admin reads everyone's for the report. Nobody
 * edits a punch — the times are server-set so the record stays evidence.
 */
class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canSignIn()
            && ($user->isMr() || $user->isAdmin() || $user->can('attendance.view'));
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->canSignIn()
            && ($user->isAdmin()
                || $user->can('attendance.view')
                || (int) $attendance->mr_id === (int) $user->id);
    }

    /** Checking in and out: the field rep, from the app, for themselves. */
    public function punch(User $user): bool
    {
        return $user->canSignIn() && $user->isMr();
    }
}
