<?php

namespace App\Modules\Auth\Exceptions;

use RuntimeException;

/**
 * Raised by AuthService when a sign-in must not proceed. The service decides
 * *why*; each controller decides how to show it (a redirect for the panel, a
 * 401 envelope for the apps), so neither knows the other's concerns.
 */
class AuthenticationFailed extends RuntimeException
{
    private function __construct(string $message, public readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function badCredentials(): self
    {
        return new self('The provided credentials do not match our records.', 'credentials');
    }

    public static function disabled(): self
    {
        return new self('This account has been disabled. Contact your administrator.', 'disabled');
    }

    public static function wrongPanel(): self
    {
        return new self('This account signs in through the mobile app, not the panel.', 'wrong_surface');
    }

    public static function wrongApp(): self
    {
        return new self('This account signs in through the web panel, not the app.', 'wrong_surface');
    }

    public static function wrongAppForRole(): self
    {
        return new self('This account does not belong to this app.', 'wrong_app');
    }
}
