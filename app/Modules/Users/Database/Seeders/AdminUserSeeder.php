<?php

namespace App\Modules\Users\Database\Seeders;

use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Database\Seeder;

/**
 * The first admin — nobody can self-register, so without this row the panel has
 * no way in. Credentials come from the environment; the defaults are dev-only
 * and the deploy sets ADMIN_EMAIL / ADMIN_PASSWORD.
 *
 * Idempotent and non-destructive: an existing admin keeps its password, it only
 * (re)gains the admin role and is re-enabled.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@prishapharma.local');
        $password = (string) env('ADMIN_PASSWORD', 'Admin@12345');

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $user = User::create([
                'name' => (string) env('ADMIN_NAME', 'Prisha Admin'),
                'email' => $email,
                'mobile' => env('ADMIN_MOBILE') ?: null,
                'role' => UserRole::Admin,
                'is_active' => true,
                'password' => $password,
            ]);

            $this->command?->info("Admin created: {$email} / {$password}");
        } else {
            $user->forceFill(['role' => UserRole::Admin->value, 'is_active' => true])->save();
            $this->command?->info("Admin already present, password untouched: {$email}");
        }

        $user->syncRoles([UserRole::Admin->value]);
    }
}
