<?php

namespace Tests\Feature\Access;

use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Regions\Models\Region;
use App\Modules\Users\Enums\UserRole;
use Tests\TestCase;

/**
 * The grant screen: an admin widens one telecaller's panel rights.
 *
 * `access.manage` is deliberately outside `PermissionCatalog::grantable()`, so
 * a telecaller can never reach this screen and widen its own rights.
 */
class PermissionPanelTest extends TestCase
{
    public function test_an_admin_lists_the_telecallers(): void
    {
        $region = Region::factory()->create();
        $this->telecaller([$region], ['name' => 'Ravi Caller']);
        $this->mr($region, ['name' => 'Asha Rep']);

        $this->actingAs($this->admin())->get('/access/permissions')
            ->assertOk()
            ->assertSee('Ravi Caller')
            // MRs hold no panel permissions at all, so they are not listed.
            ->assertDontSee('Asha Rep');
    }

    public function test_an_admin_opens_one_telecallers_grants(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($this->admin())->get("/access/permissions/{$telecaller->id}")
            ->assertOk()
            ->assertViewHas('groups', fn (array $groups) => ! array_key_exists('access', $groups));
    }

    public function test_an_admin_grants_an_extra_permission(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->assertFalse($telecaller->can('visits.view'));

        $this->actingAs($this->admin())->put("/access/permissions/{$telecaller->id}", [
            'permissions' => ['visits.view', 'attendance.view'],
        ])->assertRedirect(route('access.permissions.edit', $telecaller->id));

        $telecaller = $telecaller->fresh();
        $this->assertTrue($telecaller->can('visits.view'));
        $this->assertTrue($telecaller->can('attendance.view'));
    }

    /** Direct grants are synced, so unticking a box takes the right away. */
    public function test_unticking_a_box_removes_the_grant(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($this->admin())->put("/access/permissions/{$telecaller->id}", [
            'permissions' => ['visits.view'],
        ]);
        $this->assertTrue($telecaller->fresh()->can('visits.view'));

        $this->actingAs($this->admin())->put("/access/permissions/{$telecaller->id}", [
            'permissions' => [],
        ]);
        $this->assertFalse($telecaller->fresh()->can('visits.view'));
    }

    /**
     * The baseline lives on the role, so a direct sync must not strip it —
     * that is the whole reason grants are stored as direct permissions.
     */
    public function test_syncing_grants_leaves_the_role_baseline_intact(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($this->admin())->put("/access/permissions/{$telecaller->id}", [
            'permissions' => [],
        ]);

        $telecaller = $telecaller->fresh();

        foreach (PermissionCatalog::defaultsFor(UserRole::Telecaller) as $baseline) {
            $this->assertTrue($telecaller->can($baseline), "lost the role baseline {$baseline}");
        }
    }

    /** A crafted POST must not hand out something the admin never offered. */
    public function test_a_permission_outside_the_grantable_catalog_is_filtered_out(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($this->admin())->put("/access/permissions/{$telecaller->id}", [
            'permissions' => ['access.manage', 'mrs.create', 'telecallers.delete', 'visits.view'],
        ])->assertRedirect();

        $telecaller = $telecaller->fresh();
        $this->assertTrue($telecaller->can('visits.view'));
        $this->assertFalse($telecaller->can('access.manage'), 'a telecaller must never widen its own rights');
        $this->assertFalse($telecaller->can('mrs.create'));
        $this->assertFalse($telecaller->can('telecallers.delete'));
    }

    public function test_an_unknown_permission_name_is_a_validation_error(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($this->admin())
            ->from("/access/permissions/{$telecaller->id}")
            ->put("/access/permissions/{$telecaller->id}", ['permissions' => ['not.a.permission']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_an_mr_cannot_be_given_panel_rights_through_this_screen(): void
    {
        $mr = $this->mr(Region::factory()->create());

        $this->actingAs($this->admin())->get("/access/permissions/{$mr->id}")->assertNotFound();
        $this->actingAs($this->admin())
            ->put("/access/permissions/{$mr->id}", ['permissions' => ['visits.view']])
            ->assertNotFound();
    }

    public function test_a_telecaller_cannot_reach_the_grant_screen(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($telecaller)->get('/access/permissions')->assertForbidden();
        $this->actingAs($telecaller)->get("/access/permissions/{$telecaller->id}")->assertForbidden();
        $this->actingAs($telecaller)
            ->put("/access/permissions/{$telecaller->id}", ['permissions' => ['access.manage']])
            ->assertForbidden();
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/access/permissions')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/access/permissions')->assertRedirect(route('login'));
    }

    public function test_the_catalog_gives_the_admin_everything_and_the_mr_nothing(): void
    {
        $this->assertSame(PermissionCatalog::all(), PermissionCatalog::defaultsFor(UserRole::Admin));
        $this->assertSame([], PermissionCatalog::defaultsFor(UserRole::Mr));

        $grantable = PermissionCatalog::grantable();
        $this->assertArrayNotHasKey('access', $grantable);
        $this->assertArrayNotHasKey('mrs', $grantable);
        $this->assertArrayNotHasKey('telecallers', $grantable);
    }
}
