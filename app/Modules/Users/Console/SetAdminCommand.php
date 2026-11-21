<?php

namespace App\Modules\Users\Console;

use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Create the panel admin, or reset an existing one's password.
 *
 * AdminUserSeeder deliberately never touches an existing account's password,
 * so there has to be a deliberate way to change it. This is it — and it keeps
 * real credentials out of `.env.example` and out of git.
 *
 *   php artisan users:set-admin you@example.com
 *   php artisan users:set-admin you@example.com --password=secret --name="Your Name"
 *
 * Called without --password it prompts, so the password stays out of shell
 * history.
 */
class SetAdminCommand extends Command
{
    protected $signature = 'users:set-admin
                            {email : Email the admin signs in with}
                            {--password= : New password; prompted for when omitted}
                            {--name= : Display name, only used when creating}
                            {--mobile= : Mobile number, only used when creating}';

    protected $description = 'Create a panel admin or reset an existing admin password';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->option('password') ?: $this->secret('New password'));

        $mobile = $this->option('mobile') ?: null;

        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'mobile' => $mobile],
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8'],
                // Reported here rather than as a constraint violation from the
                // driver, so a clashing mobile reads as a message.
                'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($email, 'email')],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        $created = $user === null;

        if ($created) {
            $user = new User(['email' => $email]);
            $user->name = (string) ($this->option('name') ?: 'Prisha Admin');
            $user->mobile = $mobile;
        }

        $user->forceFill([
            'role' => UserRole::Admin->value,
            'is_active' => true,
            'password' => $password,
        ])->save();

        $user->syncRoles([UserRole::Admin->value]);

        // A password change should not leave old app sessions alive.
        $user->tokens()->delete();

        $this->info(($created ? 'Admin created: ' : 'Admin password reset: ').$email);

        if (str_contains(strtolower($password), '1234') || strlen($password) < 12) {
            $this->warn('That password is weak — change it before this panel is public.');
        }

        return self::SUCCESS;
    }
}
