<?php

namespace Tests\Feature\Access;

use App\Modules\Access\Database\Seeders\PermissionSeeder;
use App\Modules\Access\Database\Seeders\RoleSeeder;
use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Enums\UserRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Re-running the Access seeders is how a newly added capability reaches a
 * database that already exists (`PermissionCatalog`).
 *
 * Phase 11 found this only half worked: the admin was re-synced, but an
 * existing non-admin role was skipped entirely, so `reports.view` was a
 * telecaller "default" that no existing telecaller ever received. The role
 * is now topped up with what it is missing — added to, never synced.
 */
class RoleSeederTest extends TestCase
{
    /** Puts the database back to how it looked before a capability existed. */
    private function forgetCapability(string ...$names): void
    {
        $role = Role::findByName(UserRole::Telecaller->value, 'web');

        foreach ($names as $name) {
            $role->revokePermissionTo($name);
        }

        Permission::whereIn('name', $names)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function reseed(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_a_new_capability_reaches_an_existing_telecaller_role(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->forgetCapability('reports.view', 'reports.export');
        $this->assertFalse($telecaller->fresh()->can('reports.view'));

        $this->reseed();

        $this->assertTrue(
            $telecaller->fresh()->can('reports.view'),
            'a baseline permission added to the catalogue must reach existing roles',
        );
    }

    public function test_a_new_capability_reaches_the_admin_role(): void
    {
        $admin = $this->admin();

        $this->forgetCapability('reports.view', 'reports.export');
        $this->assertFalse($admin->fresh()->can('reports.view'));

        $this->reseed();

        $this->assertTrue($admin->fresh()->can('reports.view'));
        $this->assertTrue($admin->fresh()->can('reports.export'));
    }

    /** The top-up adds the role's defaults — not the whole catalogue. */
    public function test_a_telecaller_does_not_quietly_gain_a_non_default(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->forgetCapability('reports.view', 'reports.export');
        $this->reseed();

        $this->assertTrue($telecaller->fresh()->can('reports.view'));
        $this->assertFalse(
            $telecaller->fresh()->can('reports.export'),
            'export is deliberately not a telecaller default',
        );
    }

    /**
     * The Access screen stores a grant as a **direct** permission on the
     * user, so topping up the role cannot disturb it. This is what made
     * syncing unsafe and topping up safe.
     */
    public function test_a_per_user_grant_survives_a_reseed(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);
        $this->grant($telecaller, 'visits.view', 'attendance.view');

        $this->forgetCapability('reports.view', 'reports.export');
        $this->reseed();

        $fresh = $telecaller->fresh();
        $this->assertTrue($fresh->can('visits.view'), 'a hand-granted right must survive');
        $this->assertTrue($fresh->can('attendance.view'));
        $this->assertTrue($fresh->can('reports.view'));
    }

    public function test_reseeding_never_takes_a_permission_away_from_a_role(): void
    {
        $role = Role::findByName(UserRole::Telecaller->value, 'web');
        $role->givePermissionTo('visits.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $before = $role->fresh()->permissions->pluck('name')->sort()->values()->all();

        $this->reseed();

        $after = Role::findByName(UserRole::Telecaller->value, 'web')
            ->permissions->pluck('name')->sort()->values()->all();

        $this->assertSame([], array_diff($before, $after), 'the seeder must only ever add');
        $this->assertContains('visits.view', $after);
    }

    public function test_the_seeders_are_idempotent(): void
    {
        $this->reseed();
        $this->reseed();

        $this->assertSame(count(PermissionCatalog::all()), Permission::count());
        $this->assertSame(count(UserRole::cases()), Role::count());
    }

    /** The whole point: the sidebar entry appears once the seeders have run. */
    public function test_the_reports_menu_appears_for_an_admin_after_seeding(): void
    {
        $admin = $this->admin();

        $this->forgetCapability('reports.view', 'reports.export');
        $this->actingAs($admin->fresh())->get('/')->assertOk()->assertDontSee('All Reports');

        $this->reseed();

        $this->app['auth']->forgetGuards();
        $this->actingAs($admin->fresh())->get('/')->assertOk()->assertSee('All Reports');
    }
}
