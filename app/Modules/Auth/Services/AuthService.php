<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Exceptions\AuthenticationFailed;
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Every sign-in rule lives here: credentials, the is_active flag, and which
 * surface a role may use. Panel and API controllers call this and only map the
 * outcome onto HTTP.
 */
class AuthService implements AuthServiceInterface
{
    /**
     * A real bcrypt hash of a throwaway value. Checked when no user matched, so
     * an unknown email costs the same time as a wrong password and the endpoint
     * cannot be used to enumerate accounts.
     */
    private const NO_SUCH_USER_HASH = '$2y$12$/2ADeAL0J0HhDcp59qs4sefvE4usA/wi24ms.flDru2iJF1ddY2ni';

    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function authenticatePanel(string $email, string $password, bool $remember = false): User
    {
        $user = $this->verify($email, $password);

        if (! $user->role->hasPanelAccess()) {
            throw AuthenticationFailed::wrongPanel();
        }

        Auth::login($user, $remember);
        request()->session()->regenerate();

        return $user;
    }

    public function logoutPanel(): void
    {
        Auth::logout();

        $session = request()->session();
        $session->invalidate();
        $session->regenerateToken();
    }

    public function issueAppToken(string $email, string $password, ?UserRole $expected, string $deviceName): array
    {
        $user = $this->verify($email, $password);

        if (! $user->role->hasAppAccess()) {
            throw AuthenticationFailed::wrongApp();
        }

        if ($expected !== null && $user->role !== $expected) {
            throw AuthenticationFailed::wrongAppForRole();
        }

        // One live token per device name keeps re-logins from piling up rows.
        $user->tokens()->where('name', $deviceName)->delete();

        return [
            'token' => $user->createToken($deviceName, [$user->role->tokenAbility()])->plainTextToken,
            'user' => $user,
        ];
    }

    /** Shared credential + account-state check for both surfaces. */
    private function verify(string $email, string $password): User
    {
        $user = $this->users->findByEmail($email);
        $matches = Hash::check($password, $user->password ?? self::NO_SUCH_USER_HASH);

        // A role-less row predates Phase 1; it is treated as no account at all.
        if ($user === null || ! $matches || $user->role === null) {
            throw AuthenticationFailed::badCredentials();
        }

        if (! $user->is_active) {
            throw AuthenticationFailed::disabled();
        }

        return $user;
    }
}
