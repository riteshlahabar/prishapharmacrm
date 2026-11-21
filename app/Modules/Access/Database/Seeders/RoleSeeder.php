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
 * never invisible to the admin. Telecaller is only *seeded* with defaults —
 * syncing would wipe the per-user grants an admin made in the panel, so the
 * role's own permissions are left alone once it exists.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (UserRole::cases() as $case) {
            $existed = Role::where(['name' => $case->value, 'guard_name' => 'web'])->exists();
            $role = Role::findOrCreate($case->value, 'web');

            if ($case === UserRole::Admin) {
                $role->syncPermissions(PermissionCatalog::all());

                continue;
            }

            if (! $existed) {
                $role->syncPermissions(PermissionCatalog::defaultsFor($case));
            }
        }

        $this->command?->info('Roles seeded: '.implode(', ', UserRole::values()));
    }
}
