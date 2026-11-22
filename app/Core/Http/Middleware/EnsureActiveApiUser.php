<?php

namespace App\Core\Http\Middleware;

use App\Core\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-checks the account behind a bearer token on every API request.
 *
 * A Sanctum token is a frozen snapshot: `canSignIn()` was true and the role
 * ability was stamped on it at login, and nothing looked again. So before
 * Phase 6 an admin could deactivate a rep, or change their role, and the
 * rep's existing token kept full access — including filing new visits —
 * until it was manually deleted.
 *
 * Runs after `auth:sanctum` and no-ops on guest routes such as login, so the
 * ability middleware still answers for a token minted by the other app.
 */
final class EnsureActiveApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Deactivated, or left without a role. The apps treat this reason as
        // "sign out and clear the token" (docs/api/v1-common.md).
        if (! $user->canSignIn()) {
            return ApiResponse::error(
                'This account is no longer active.',
                ['reason' => 'account_inactive'],
                403,
            );
        }

        $token = $user->currentAccessToken();

        // The token names the app it was minted for. If the account's role has
        // since moved, that ability no longer describes this user, so the
        // token is stale even though it is authentic.
        if ($token !== null && ! $token->can("role:{$user->role->value}")) {
            return ApiResponse::error(
                'This account no longer matches the app this token was issued for.',
                ['reason' => 'role_changed'],
                403,
            );
        }

        return $next($request);
    }
}
