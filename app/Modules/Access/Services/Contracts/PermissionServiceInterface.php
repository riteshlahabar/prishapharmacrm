<?php

namespace App\Modules\Access\Services\Contracts;

use App\Modules\Users\Models\User;
use Illuminate\Support\Collection;

/**
 * Reads and writes what a single telecaller may do in the panel.
 */
interface PermissionServiceInterface
{
    /**
     * Permission names currently in force for this user, direct grants plus
     * the ones that come from the telecaller role.
     *
     * @return Collection<int, string>
     */
    public function effectiveFor(User $user): Collection;

    /** Names granted directly to the user, i.e. the editable set. */
    public function directFor(User $user): Collection;

    /** Names the telecaller role gives everyone, shown as locked-on. */
    public function fromRoleFor(User $user): Collection;

    /**
     * Replace the user's direct grants with $names. Anything outside
     * PermissionCatalog::grantable() is ignored, so a crafted form cannot hand
     * out `access.manage`.
     *
     * @param  array<int, string>  $names
     * @return array<int, string> the names actually stored
     */
    public function syncDirect(User $user, array $names): array;
}
