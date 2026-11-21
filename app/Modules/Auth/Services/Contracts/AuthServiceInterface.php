<?php

namespace App\Modules\Auth\Services\Contracts;

use App\Modules\Auth\Exceptions\AuthenticationFailed;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;

/**
 * The single place that decides who may sign in, and through which surface.
 *
 * @throws AuthenticationFailed
 */
interface AuthServiceInterface
{
    /** Start a panel session. Admins and telecallers only. */
    public function authenticatePanel(string $email, string $password, bool $remember = false): User;

    /** End the panel session and invalidate it. */
    public function logoutPanel(): void;

    /**
     * Issue a Sanctum token for a Flutter app. $expected lets an app state the
     * role it serves so a telecaller cannot sign into the MR app.
     *
     * @return array{token: string, user: User}
     */
    public function issueAppToken(string $email, string $password, ?UserRole $expected, string $deviceName): array;
}
