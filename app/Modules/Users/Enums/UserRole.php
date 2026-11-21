<?php

namespace App\Modules\Users\Enums;

/**
 * The three kinds of account in the system. `admin` and `telecaller` sign into
 * the Cuba panel; `mr` and `telecaller` sign into their Flutter app. The value
 * doubles as the spatie role name, so the enum is the single source of truth
 * for both the users.role column and the seeded roles.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Mr = 'mr';
    case Telecaller = 'telecaller';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Mr => 'Medical Representative',
            self::Telecaller => 'Telecaller',
        };
    }

    /** Does this role get into the web panel? */
    public function hasPanelAccess(): bool
    {
        return $this !== self::Mr;
    }

    /** Does this role get a Sanctum token for a Flutter app? */
    public function hasAppAccess(): bool
    {
        return $this !== self::Admin;
    }

    /**
     * Ability stamped on the Sanctum token so each app can refuse a login by a
     * user belonging to the other app (see docs/api/v1-auth.md).
     */
    public function tokenAbility(): string
    {
        return "role:{$this->value}";
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Roles an admin may create from the panel. */
    public static function assignable(): array
    {
        return [self::Mr, self::Telecaller];
    }
}
