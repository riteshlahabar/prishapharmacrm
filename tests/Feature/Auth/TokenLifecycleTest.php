<?php

namespace Tests\Feature\Auth;

use App\Modules\Regions\Models\Region;
use App\Modules\Users\Enums\UserRole;
use Tests\TestCase;

/**
 * A Sanctum token is a snapshot taken at login. `EnsureActiveApiUser` re-checks
 * the account behind it on every request, which is what stops a deactivated or
 * re-roled user carrying on until someone deletes the row by hand
 * (docs/authorization.md).
 */
class TokenLifecycleTest extends TestCase
{
    public function test_me_returns_the_signed_in_account(): void
    {
        $region = Region::factory()->named('Thane', 'THN')->create();
        $mr = $this->mr($region);

        $this->actingAsApp($mr)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $mr->id)
            ->assertJsonPath('data.role', 'mr')
            ->assertJsonPath('data.region_id', $region->id)
            ->assertJsonPath('data.regions.0.code', 'THN');
    }

    public function test_me_never_leaks_the_password_hash(): void
    {
        $mr = $this->mr();

        $data = $this->actingAsApp($mr)->getJson('/api/v1/auth/me')->json('data');

        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    public function test_logout_deletes_only_the_calling_token(): void
    {
        $mr = $this->mr();
        $other = $mr->createToken('other-device', ['role:mr']);

        $this->actingAsApp($mr, 'this-device')->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $mr->tokens()->count());
        $this->assertSame('other-device', $mr->tokens()->firstOrFail()->name);
        $this->assertNotNull($other);
    }

    public function test_a_deactivated_account_loses_access_mid_token(): void
    {
        $mr = $this->mr();
        $token = $this->appTokenFor($mr);

        $this->actingAsApp($token)->getJson('/api/v1/visits')->assertOk();

        $mr->forceFill(['is_active' => false])->save();

        $this->actingAsApp($token)->getJson('/api/v1/visits')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'account_inactive');
    }

    public function test_a_deactivated_account_cannot_write_either(): void
    {
        $mr = $this->mr();
        $token = $this->appTokenFor($mr);

        $mr->forceFill(['is_active' => false])->save();

        $this->actingAsApp($token)->postJson('/api/v1/attendance/check-in', [])
            ->assertStatus(403)
            ->assertJsonPath('errors.reason', 'account_inactive');
    }

    /** The ability on the token no longer describes the account's role. */
    public function test_a_role_change_invalidates_an_existing_token(): void
    {
        $mr = $this->mr();
        $token = $this->appTokenFor($mr);

        $this->actingAsApp($token)->getJson('/api/v1/visits')->assertOk();

        $mr->forceFill(['role' => UserRole::Telecaller->value])->save();

        $this->actingAsApp($token)->getJson('/api/v1/visits')
            ->assertStatus(403)
            ->assertJsonPath('errors.reason', 'role_changed');
    }

    public function test_a_user_left_without_a_role_is_treated_as_inactive(): void
    {
        $mr = $this->mr();
        $token = $this->appTokenFor($mr);

        $mr->forceFill(['role' => null])->save();

        $this->actingAsApp($token)->getJson('/api/v1/visits')
            ->assertStatus(403)
            ->assertJsonPath('errors.reason', 'account_inactive');
    }

    public function test_restoring_the_account_restores_access(): void
    {
        $mr = $this->mr();
        $token = $this->appTokenFor($mr);

        $mr->forceFill(['is_active' => false])->save();
        $this->actingAsApp($token)->getJson('/api/v1/visits')->assertStatus(403);

        $mr->forceFill(['is_active' => true])->save();
        $this->actingAsApp($token)->getJson('/api/v1/visits')->assertOk();
    }

    public function test_a_token_minted_for_the_other_app_is_refused(): void
    {
        $mr = $this->mr();

        // Authentic token, wrong ability: Sanctum's `ability:` gate refuses it.
        $this->actingAsAppWithAbility($mr, UserRole::Telecaller)
            ->getJson('/api/v1/visits')
            ->assertStatus(403)
            ->assertJsonPath('errors.reason', 'role_changed');
    }

    public function test_an_expired_or_absent_token_is_unauthenticated(): void
    {
        $this->asGuestApp()->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'unauthenticated');
    }

    public function test_a_garbage_bearer_token_is_unauthenticated(): void
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer 999|notarealtokenatall',
        ])->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'unauthenticated');
    }
}
