<?php

namespace App\Modules\Access\Database\Seeders;

use App\Modules\Access\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every permission in PermissionCatalog. Idempotent: re-running after
 * adding a capability to the catalog only inserts the new rows.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $this->command?->info('Permissions seeded: '.count(PermissionCatalog::all()));
    }
}
