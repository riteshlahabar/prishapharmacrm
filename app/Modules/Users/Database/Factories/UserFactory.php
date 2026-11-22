<?php

namespace App\Modules\Users\Database\Factories;

use App\Modules\Regions\Models\Region;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Accounts for the test suite and the demo seeders.
 *
 * The default state deliberately has **no role**: a role-less row is what a
 * pre-Phase-1 user looks like, and `canSignIn()` refuses it. Every test that
 * wants a usable account says which kind with `admin()`, `mr()` or
 * `telecaller()`, so no test silently depends on a default.
 *
 * `withRole()` also assigns the matching spatie role, because the panel gates
 * on permissions rather than on `users.role`. Seed the Access seeders first
 * (Tests\TestCase does) or the role will not exist.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Hashed once: bcrypt on every row makes a seeding run crawl. */
    protected static ?string $password = null;

    /** The password every factory account signs in with. */
    public const PASSWORD = 'Password#123';

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // Digits only, which satisfies the panel's mobile regex.
            'mobile' => fake()->unique()->numerify('9#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make(self::PASSWORD),
            'role' => null,
            'region_id' => null,
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->withRole(UserRole::Admin);
    }

    /** An MR travels one territory, so `region_id` carries it. */
    public function mr(Region|int|null $region = null): static
    {
        return $this->withRole(UserRole::Mr)->state([
            'region_id' => $region instanceof Region ? $region->id : $region,
        ]);
    }

    /**
     * A telecaller's territories live in the `telecaller_region` pivot, not on
     * the row, so pass regions to `inRegions()` after creating.
     */
    public function telecaller(): static
    {
        return $this->withRole(UserRole::Telecaller);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Attach a telecaller's regions once the row exists. */
    public function inRegions(Region|int ...$regions): static
    {
        $ids = array_map(fn ($r) => $r instanceof Region ? $r->id : $r, $regions);

        return $this->afterCreating(fn (User $user) => $user->regions()->sync($ids));
    }

    /**
     * Sets `users.role` and the spatie role together. They are two separate
     * things — the column drives the API's token ability, the spatie role
     * drives the panel's permissions — and a user needs both to be usable.
     */
    public function withRole(UserRole $role): static
    {
        return $this->state(['role' => $role])
            ->afterCreating(function (User $user) use ($role) {
                if (Role::where('name', $role->value)->exists()) {
                    $user->syncRoles([$role->value]);
                }
            });
    }
}
