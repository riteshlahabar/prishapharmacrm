<?php

namespace App\Modules\Access\Database\Seeders;

use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Users\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the three roles and gives each its default permissions.
 *
 * Admin is re-synced to the full catalog on every run so a new capability is
 * never invisible to the admin.
 *
 * Other roles are **topped up, never synced**. Syncing would drop whatever a
 * role has gained since; skipping an existing role entirely — which this did
 * until Phase 11 — meant a newly added baseline permission reached fresh
 * installs only, so `reports.view` was a telecaller "default" that no
 * existing telecaller ever got. Adding only what is missing is safe: the
 * per-user grants the Access screen manages are *direct permissions on the
 * user* (PermissionService::syncDirect), not on the role, so nothing here
 * can disturb them.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $added = [];

        foreach (UserRole::cases() as $case) {
            $role = Role::findOrCreate($case->value, 'web');

            if ($case === UserRole::Admin) {
                $role->syncPermissions(PermissionCatalog::all());

                continue;
            }

            // Only what the role is missing, so a re-run adds a new baseline
            // capability without touching anything already granted.
            $missing = array_values(array_diff(
                PermissionCatalog::defaultsFor($case),
                $role->permissions->pluck('name')->all(),
            ));

            if ($missing !== []) {
                $role->givePermissionTo($missing);
                $added[$case->value] = $missing;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Roles seeded: '.implode(', ', UserRole::values()));

        foreach ($added as $role => $names) {
            $this->command?->info("  {$role} gained: ".implode(', ', $names));
        }
    }
}
