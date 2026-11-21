<?php

namespace Database\Seeders;

use App\Modules\Access\Database\Seeders\PermissionSeeder;
use App\Modules\Access\Database\Seeders\RoleSeeder;
use App\Modules\Users\Database\Seeders\AdminUserSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Root seeder: calls each module's own seeders in dependency order. Modules own
 * their seed data (docs/conventions.md); this file only sequences them.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
