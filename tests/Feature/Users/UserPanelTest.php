<?php

namespace Tests\Feature\Users;

use App\Modules\Regions\Models\Region;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin CRUD for the two app-facing roles. Both resources are the same screens
 * over the same table; the role comes from the **route**, never the body, so a
 * crafted POST cannot mint an admin.
 */
class UserPanelTest extends TestCase
{
    public function test_each_list_shows_only_its_own_role(): void
    {
        $region = Region::factory()->create();
        $this->mr($region);
        $this->mr($region);
        $this->telecaller([$region]);

        $this->actingAs($this->admin())->get('/mrs')
            ->assertOk()
            ->assertViewHas('users', fn (LengthAwarePaginator $p) => $p->total() === 2);

        $this->actingAs($this->admin())->get('/telecallers')
            ->assertOk()
            ->assertViewHas('users', fn (LengthAwarePaginator $p) => $p->total() === 1);
    }

    public function test_an_admin_creates_an_mr_with_one_territory(): void
    {
        $region = Region::factory()->create();
        $other = Region::factory()->create();

        $this->actingAs($this->admin())->post('/mrs', [
            'name' => 'Asha Rep',
            'email' => 'Asha@Example.COM',
            'mobile' => '9811111111',
            'password' => 'Password#123',
            'password_confirmation' => 'Password#123',
            // An MR travels one territory: the first id wins, the rest is noise.
            'region_ids' => [$region->id, $other->id],
            'is_active' => '1',
        ])->assertRedirect(route('mrs.index'));

        $created = User::where('email', 'asha@example.com')->firstOrFail();
        $this->assertSame(UserRole::Mr, $created->role);
        $this->assertSame($region->id, $created->region_id);
        $this->assertTrue($created->hasRole('mr'));
        $this->assertTrue(Hash::check('Password#123', $created->password));
    }

    public function test_an_admin_creates_a_telecaller_covering_several_regions(): void
    {
        $a = Region::factory()->create();
        $b = Region::factory()->create();

        $this->actingAs($this->admin())->post('/telecallers', [
            'name' => 'Ravi Caller',
            'email' => 'ravi@example.com',
            'mobile' => '9822222222',
            'password' => 'Password#123',
            'password_confirmation' => 'Password#123',
            'region_ids' => [$a->id, $b->id],
            'is_active' => '1',
        ])->assertRedirect(route('telecallers.index'));

        $created = User::where('email', 'ravi@example.com')->firstOrFail();
        $this->assertSame(UserRole::Telecaller, $created->role);
        $this->assertNull($created->region_id);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $created->regions->pluck('id')->all());
        $this->assertTrue($created->hasRole('telecaller'));
    }

    /** The role is the route's, so it cannot be posted. */
    public function test_a_posted_role_is_ignored(): void
    {
        $this->actingAs($this->admin())->post('/mrs', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'mobile' => '9833333333',
            'password' => 'Password#123',
            'password_confirmation' => 'Password#123',
            'role' => 'admin',
        ])->assertRedirect(route('mrs.index'));

        $this->assertSame(UserRole::Mr, User::where('email', 'sneaky@example.com')->firstOrFail()->role);
    }

    public function test_the_email_and_mobile_must_be_unique(): void
    {
        $existing = $this->mr(null, ['email' => 'taken@example.com', 'mobile' => '9844444444']);

        $this->actingAs($this->admin())
            ->from('/mrs/create')
            ->post('/mrs', [
                'name' => 'Clash',
                'email' => $existing->email,
                'mobile' => $existing->mobile,
                'password' => 'Password#123',
                'password_confirmation' => 'Password#123',
            ])
            ->assertSessionHasErrors(['email', 'mobile']);
    }

    public function test_the_password_must_be_confirmed_and_long_enough(): void
    {
        $this->actingAs($this->admin())
            ->from('/mrs/create')
            ->post('/mrs', [
                'name' => 'Weak',
                'email' => 'weak@example.com',
                'mobile' => '9855555555',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors('password');
    }

    /** A blank password field on edit means "keep the current one". */
    public function test_updating_without_a_password_keeps_the_existing_one(): void
    {
        $mr = $this->mr(null, ['email' => 'keep@example.com', 'mobile' => '9866666666']);
        $before = $mr->password;

        $this->actingAs($this->admin())->put("/mrs/{$mr->id}", [
            'name' => 'Renamed Rep',
            'email' => 'keep@example.com',
            'mobile' => '9866666666',
            'password' => '',
            'is_active' => '1',
        ])->assertRedirect(route('mrs.index'));

        $mr->refresh();
        $this->assertSame('Renamed Rep', $mr->name);
        $this->assertSame($before, $mr->password);
    }

    public function test_a_supplied_password_is_hashed_and_replaces_the_old_one(): void
    {
        $mr = $this->mr(null, ['email' => 'rot@example.com', 'mobile' => '9877777777']);

        $this->actingAs($this->admin())->put("/mrs/{$mr->id}", [
            'name' => $mr->name,
            'email' => 'rot@example.com',
            'mobile' => '9877777777',
            'password' => 'Brand#New456',
            'password_confirmation' => 'Brand#New456',
            'is_active' => '1',
        ])->assertRedirect(route('mrs.index'));

        $mr->refresh();
        $this->assertTrue(Hash::check('Brand#New456', $mr->password));
        $this->assertNotSame('Brand#New456', $mr->password);
    }

    public function test_a_user_keeps_their_own_email_on_edit(): void
    {
        $mr = $this->mr(null, ['email' => 'mine@example.com', 'mobile' => '9888888888']);

        $this->actingAs($this->admin())->put("/mrs/{$mr->id}", [
            'name' => 'Same Email',
            'email' => 'mine@example.com',
            'mobile' => '9888888888',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();
    }

    public function test_changing_a_telecallers_regions_replaces_the_old_set(): void
    {
        $a = Region::factory()->create();
        $b = Region::factory()->create();
        $c = Region::factory()->create();

        $telecaller = $this->telecaller([$a, $b], ['email' => 'tc@example.com', 'mobile' => '9899999999']);

        $this->actingAs($this->admin())->put("/telecallers/{$telecaller->id}", [
            'name' => $telecaller->name,
            'email' => 'tc@example.com',
            'mobile' => '9899999999',
            'region_ids' => [$c->id],
            'is_active' => '1',
        ])->assertRedirect(route('telecallers.index'));

        $this->assertSame([$c->id], $telecaller->refresh()->regions->pluck('id')->all());
    }

    public function test_a_region_must_exist(): void
    {
        $this->actingAs($this->admin())
            ->from('/mrs/create')
            ->post('/mrs', [
                'name' => 'Ghost Region',
                'email' => 'ghost@example.com',
                'mobile' => '9900000001',
                'password' => 'Password#123',
                'password_confirmation' => 'Password#123',
                'region_ids' => [99999],
            ])
            ->assertSessionHasErrors('region_ids.0');
    }

    public function test_an_account_can_be_toggled_off_without_opening_the_form(): void
    {
        $mr = $this->mr();

        $this->actingAs($this->admin())->patch("/mrs/{$mr->id}/toggle")
            ->assertRedirect(route('mrs.index'));

        $this->assertFalse($mr->refresh()->is_active);
    }

    public function test_an_admin_deletes_an_mr(): void
    {
        $mr = $this->mr();

        $this->actingAs($this->admin())->delete("/mrs/{$mr->id}")
            ->assertRedirect(route('mrs.index'));

        $this->assertDatabaseMissing('users', ['id' => $mr->id]);
    }

    /** The two resources must not reach across into each other's role. */
    public function test_a_telecaller_cannot_be_managed_through_the_mr_routes(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->actingAs($this->admin())->get("/mrs/{$telecaller->id}")->assertNotFound();
        $this->actingAs($this->admin())->get("/mrs/{$telecaller->id}/edit")->assertNotFound();
        $this->actingAs($this->admin())->delete("/mrs/{$telecaller->id}")->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $telecaller->id]);
    }

    public function test_an_admin_cannot_be_reached_through_either_resource(): void
    {
        $admin = $this->admin();

        $this->actingAs($this->admin())->get("/mrs/{$admin->id}")->assertNotFound();
        $this->actingAs($this->admin())->get("/telecallers/{$admin->id}")->assertNotFound();
    }

    /** User administration is not grantable to a telecaller at all. */
    public function test_a_telecaller_cannot_reach_user_administration(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        foreach (['/mrs', '/telecallers', '/mrs/create', '/telecallers/create'] as $path) {
            $this->actingAs($telecaller)->get($path)->assertForbidden();
        }
    }

    public function test_an_mr_has_no_panel_access(): void
    {
        $this->actingAs($this->mr())->get('/mrs')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_form(): void
    {
        $this->get('/mrs')->assertRedirect(route('login'));
    }
}
