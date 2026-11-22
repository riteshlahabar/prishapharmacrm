<?php

namespace Tests;

use App\Modules\Access\Database\Seeders\PermissionSeeder;
use App\Modules\Access\Database\Seeders\RoleSeeder;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Database\Factories\UserFactory;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

/**
 * Base for the whole suite (Phase 8).
 *
 * Two things every test needs and should not restate:
 *
 * - **A migrated database.** `RefreshDatabase` on the sqlite `:memory:`
 *   connection from phpunit.xml, so every module's own migrations under
 *   its `Database/Migrations` directory all run.
 * - **Roles and permissions.** The panel gates on spatie permissions, so
 *   without the Access seeders every panel assertion would be a 403 for the
 *   wrong reason. Seeded once per test, and the registrar's cache is dropped
 *   because it survives between tests in one process and would otherwise
 *   serve the previous test's (rolled-back) permission ids.
 *
 * Account helpers live here rather than in a trait because every module test
 * needs them: the three roles are the axis the whole authorization model turns
 * on (docs/authorization.md).
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** The password every factory-made account signs in with. */
    protected const PASSWORD = UserFactory::PASSWORD;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** An admin: every panel permission, no app access. */
    protected function admin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    /** An MR, optionally pinned to a territory. App-only — no panel rights. */
    protected function mr(Region|int|null $region = null, array $attributes = []): User
    {
        return User::factory()->mr($region)->create($attributes);
    }

    /**
     * A telecaller covering the given regions. Gets the role's default panel
     * permissions (PermissionCatalog::defaultsFor) and an app token ability.
     */
    protected function telecaller(array $regions = [], array $attributes = []): User
    {
        return User::factory()
            ->telecaller()
            ->inRegions(...$regions)
            ->create($attributes);
    }

    /**
     * Mint a real Sanctum token carrying the one ability the user's role mints
     * (`role:mr` / `role:telecaller`). A real token rather than Sanctum's
     * `actingAs` keeps the ability middleware in play, which is what refuses a
     * token minted by the other app.
     */
    protected function appTokenFor(User $user, ?string $deviceName = null): string
    {
        return $user->createToken(
            $deviceName ?? 'phpunit',
            [$user->role?->tokenAbility() ?? '*'],
        )->plainTextToken;
    }

    /**
     * Authenticate as an app would. Pass a user to mint a token, or a token
     * string from `appTokenFor()` to keep using one across several requests.
     *
     * Guards are forgotten first. Laravel keeps one container for the whole
     * test, so the guard holds on to the user it resolved for the previous
     * request — which would quietly defeat every assertion about an account
     * changing *behind* a live token (deactivation, a role move).
     */
    protected function actingAsApp(User|string $user, ?string $deviceName = null): static
    {
        $token = $user instanceof User ? $this->appTokenFor($user, $deviceName) : $user;

        $this->app['auth']->forgetGuards();

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ]);
    }

    /**
     * A bearer token minted with a deliberately wrong ability, for the
     * "a token from the other app is refused" assertions.
     */
    protected function actingAsAppWithAbility(User $user, UserRole $ability): static
    {
        $token = $user->createToken('phpunit', [$ability->tokenAbility()]);

        return $this->actingAsApp($token->plainTextToken);
    }

    /** JSON headers with no credentials, for the 401 assertions. */
    protected function asGuestApp(): static
    {
        return $this->withHeaders(['Accept' => 'application/json']);
    }

    /** Give a user one extra panel permission without touching their role. */
    protected function grant(User $user, string ...$permissions): User
    {
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->refresh();
    }

    /**
     * Take a permission away, to assert a route really is gated.
     *
     * Revokes from the user's **roles** as well as the user: a telecaller's
     * rights arrive via the role (PermissionCatalog::defaultsFor), so revoking
     * only the direct grant would leave `can()` still true and the test would
     * pass against a route that is in fact open.
     */
    protected function revoke(User $user, string ...$permissions): User
    {
        foreach ($permissions as $permission) {
            $user->revokePermissionTo($permission);

            foreach ($user->roles as $role) {
                $role->revokePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->refresh();
    }
}
