<?php

namespace App\Modules\Dashboard\Services\Contracts;

use App\Modules\Users\Models\User;

/**
 * Builds the one read model the dashboard view renders.
 *
 * The Dashboard module owns no table. Everything here is assembled from other
 * modules' **service contracts** — never their repositories or models — so a
 * metric on this screen is the same number the module's own screen shows
 * (docs/conventions.md).
 */
interface DashboardServiceInterface
{
    /** Periods the selector offers, in days. */
    public const PERIODS = [7, 30, 90];

    public const DEFAULT_PERIOD = 30;

    /**
     * Everything the view needs, already scoped to what this user may see:
     * `days`, `from`, `to`, `can`, `tiles`, `charts`, `empty`.
     *
     * @return array<string, mixed>
     */
    public function forUser(User $user, int $days = self::DEFAULT_PERIOD): array;

    /** Clamp an arbitrary `?days=` to one of PERIODS. */
    public function period(mixed $days): int;
}
