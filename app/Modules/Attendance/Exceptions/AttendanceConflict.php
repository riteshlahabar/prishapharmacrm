<?php

namespace App\Modules\Attendance\Exceptions;

use RuntimeException;

/**
 * The check-in/check-out sequence was broken. Thrown by AttendanceService so
 * the API can answer 409 with a reason the app can branch on, rather than
 * leaving the mobile client to interpret prose.
 */
class AttendanceConflict extends RuntimeException
{
    private function __construct(string $message, public readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function alreadyCheckedIn(): self
    {
        return new self('You have already checked in today.', 'already_checked_in');
    }

    public static function notCheckedIn(): self
    {
        return new self('You have not checked in today.', 'not_checked_in');
    }

    public static function alreadyCheckedOut(): self
    {
        return new self('You have already checked out today.', 'already_checked_out');
    }
}
