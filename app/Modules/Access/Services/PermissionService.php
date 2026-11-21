<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Services\Contracts\PermissionServiceInterface;
use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Users\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Telecaller rights are stored as *direct* permissions on the user, not by
 * cloning roles: the `telecaller` role carries the baseline everyone gets, and
 * the admin adds to it per person. That way a change to the baseline reaches
 * every telecaller without touching their individual grants.
 */
class PermissionService implements PermissionServiceInterface
{
    public function effectiveFor(User $user): Collection
    {
        return $user->getAllPermissions()->pluck('name')->sort()->values();
    }

    public function directFor(User $user): Collection
    {
        return $user->getDirectPermissions()->pluck('name')->sort()->values();
    }

    public function fromRoleFor(User $user): Collection
    {
        return $user->getPermissionsViaRoles()->pluck('name')->sort()->values();
    }

    public function syncDirect(User $user, array $names): array
    {
        $allowed = [];

        foreach (PermissionCatalog::grantable() as $group => $meta) {
            foreach ($meta['abilities'] as $ability) {
                $allowed[] = "{$group}.{$ability}";
            }
        }

        $granted = array_values(array_intersect($allowed, array_unique($names)));

        $user->syncPermissions($granted);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $granted;
    }
}
