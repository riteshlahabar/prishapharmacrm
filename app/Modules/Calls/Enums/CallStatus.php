<?php

namespace App\Modules\Calls\Enums;

/**
 * How a call ended. An enum rather than free text: the point of the call
 * report is counting outcomes, and typed-in strings do not group.
 *
 * `Callback` is the only status that *expects* a `follow_up_at`; the request
 * enforces that, so a promised call-back cannot be lost.
 */
enum CallStatus: string
{
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case Callback = 'callback';
    case NotReachable = 'not_reachable';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Interested => 'Interested',
            self::NotInterested => 'Not interested',
            self::Callback => 'Call back',
            self::NotReachable => 'Not reachable',
            self::Converted => 'Converted',
        };
    }

    /** Cuba badge colour, so the report reads at a glance. */
    public function badge(): string
    {
        return match ($this) {
            self::Interested => 'primary',
            self::NotInterested => 'danger',
            self::Callback => 'warning',
            self::NotReachable => 'secondary',
            self::Converted => 'success',
        };
    }

    /** A status that asks for a date to come back on. */
    public function needsFollowUp(): bool
    {
        return $this === self::Callback;
    }

    /** The outcome the dashboard counts as a win (Phase 7 conversion rate). */
    public function isConversion(): bool
    {
        return $this === self::Converted;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
