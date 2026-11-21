<?php

namespace App\Modules\Users\Database\Seeders;

use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The first admin — nobody can self-register, so without this row the panel has
 * no way in. Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD.
 *
 * ADMIN_PASSWORD is blank in `.env.example` on purpose: a working password
 * committed to the repository is a password every clone of it shares. Left
 * blank, this generates one and prints it once. Set your own in `.env`, which
 * is git-ignored, or change it later with `php artisan users:set-admin`.
 *
 * Idempotent and non-destructive: an existing admin keeps its password, it only
 * (re)gains the admin role and is re-enabled.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) (env('ADMIN_EMAIL') ?: 'admin@prishapharma.local');

        // env() hands back '' for a blank key, not the default, so test the
        // value rather than relying on a fallback argument.
        $password = (string) env('ADMIN_PASSWORD');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16);
        }

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

            $this->command?->info("Admin created: {$email}");

            if ($generated) {
                $this->command?->warn("Generated password (shown once): {$password}");
                $this->command?->warn('Set ADMIN_PASSWORD in .env to choose your own.');
            }
        } else {
            $user->forceFill(['role' => UserRole::Admin->value, 'is_active' => true])->save();
            $this->command?->info("Admin already present, password untouched: {$email}");
        }

        $user->syncRoles([UserRole::Admin->value]);
    }
}
