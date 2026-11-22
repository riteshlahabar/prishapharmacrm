<?php

namespace Tests\Feature\Auth;

use App\Modules\Regions\Models\Region;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Models\User;
use Tests\TestCase;

/**
 * `POST /api/v1/auth/login` — the only way either Flutter app gets a token
 * (docs/api/v1-auth.md). There is no registration endpoint.
 */
class AppLoginTest extends TestCase
{
    public function test_an_mr_signs_in_and_receives_a_role_scoped_token(): void
    {
        $mr = $this->mr(Region::factory()->create());

        $response = $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $mr->email,
            'password' => self::PASSWORD,
            'role' => 'mr',
            'device_name' => 'pixel-8',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.role', 'mr')
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'token_type', 'user']]);

        $this->assertNotEmpty($response->json('data.token'));

        // The ability is what refuses this token at the telecaller endpoints.
        $this->assertTrue($mr->tokens()->firstOrFail()->can('role:mr'));
        $this->assertFalse($mr->tokens()->firstOrFail()->can('role:telecaller'));
        $this->assertSame('pixel-8', $mr->tokens()->firstOrFail()->name);
    }

    public function test_a_telecaller_signs_in_and_sees_the_regions_they_cover(): void
    {
        $region = Region::factory()->named('Pune West', 'PNW')->create();
        $telecaller = $this->telecaller([$region]);

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $telecaller->email,
            'password' => self::PASSWORD,
            'role' => 'telecaller',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'telecaller')
            // A telecaller has no single region_id; the list carries their work.
            ->assertJsonPath('data.user.region_id', null)
            ->assertJsonPath('data.user.regions.0.code', 'PNW');
    }

    public function test_the_email_is_matched_case_insensitively(): void
    {
        $mr = $this->mr(null, ['email' => 'rep@prishapharma.local']);

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => '  REP@PrishaPharma.Local  ',
            'password' => self::PASSWORD,
        ])->assertOk();

        $this->assertSame(1, $mr->tokens()->count());
    }

    public function test_a_wrong_password_is_refused_with_a_stable_reason(): void
    {
        $mr = $this->mr();

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $mr->email,
            'password' => 'not-the-password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'credentials');
    }

    /**
     * An unknown email must answer exactly as a wrong password does, or the
     * endpoint becomes an account-enumeration oracle. AuthService checks a
     * throwaway hash to keep the timing alike too.
     */
    public function test_an_unknown_email_is_refused_the_same_way_as_a_wrong_password(): void
    {
        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => 'nobody@prishapharma.local',
            'password' => 'whatever',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'credentials');
    }

    public function test_a_deactivated_account_is_refused(): void
    {
        $mr = $this->mr(null, ['is_active' => false]);

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $mr->email,
            'password' => self::PASSWORD,
        ])
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'disabled');

        $this->assertSame(0, $mr->tokens()->count());
    }

    /** A row with no role predates Phase 1 and is treated as no account. */
    public function test_a_role_less_account_cannot_sign_in(): void
    {
        $orphan = User::factory()->create(['role' => null]);

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $orphan->email,
            'password' => self::PASSWORD,
        ])
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'credentials');
    }

    public function test_an_admin_cannot_sign_into_an_app(): void
    {
        $admin = $this->admin();

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ])
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'wrong_surface');
    }

    /** The app names the role it serves, so the other app's user is refused. */
    public function test_a_telecaller_is_refused_by_the_mr_app(): void
    {
        $telecaller = $this->telecaller();

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $telecaller->email,
            'password' => self::PASSWORD,
            'role' => 'mr',
        ])
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'wrong_app');

        $this->assertSame(0, $telecaller->tokens()->count());
    }

    public function test_an_mr_is_refused_by_the_telecaller_app(): void
    {
        $mr = $this->mr();

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $mr->email,
            'password' => self::PASSWORD,
            'role' => 'telecaller',
        ])
            ->assertStatus(401)
            ->assertJsonPath('errors.reason', 'wrong_app');
    }

    public function test_an_unknown_role_is_a_validation_error_not_a_login_attempt(): void
    {
        $mr = $this->mr();

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $mr->email,
            'password' => self::PASSWORD,
            'role' => 'admin',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonStructure(['errors' => ['role']]);
    }

    public function test_missing_credentials_answer_in_the_api_envelope(): void
    {
        $this->asGuestApp()->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.reason', 'validation')
            ->assertJsonPath('errors.email.0', 'The email field is required.')
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    /** Re-logging in from one device replaces that device's token. */
    public function test_signing_in_again_from_the_same_device_does_not_pile_up_tokens(): void
    {
        $mr = $this->mr();

        foreach (range(1, 3) as $ignored) {
            $this->asGuestApp()->postJson('/api/v1/auth/login', [
                'email' => $mr->email,
                'password' => self::PASSWORD,
                'device_name' => 'same-phone',
            ])->assertOk();
        }

        $this->assertSame(1, $mr->tokens()->where('name', 'same-phone')->count());
    }

    public function test_a_second_device_keeps_its_own_token(): void
    {
        $mr = $this->mr();

        foreach (['phone', 'tablet'] as $device) {
            $this->asGuestApp()->postJson('/api/v1/auth/login', [
                'email' => $mr->email,
                'password' => self::PASSWORD,
                'device_name' => $device,
            ])->assertOk();
        }

        $this->assertSame(2, $mr->tokens()->count());
    }

    public function test_login_is_throttled(): void
    {
        $mr = $this->mr();

        // throttle:10,1 on the route — the 11th attempt is refused.
        foreach (range(1, 10) as $ignored) {
            $this->asGuestApp()->postJson('/api/v1/auth/login', [
                'email' => $mr->email,
                'password' => 'wrong',
            ])->assertStatus(401);
        }

        $this->asGuestApp()->postJson('/api/v1/auth/login', [
            'email' => $mr->email,
            'password' => 'wrong',
        ])
            ->assertStatus(429)
            ->assertJsonPath('errors.reason', 'throttled')
            ->assertHeader('Retry-After');
    }

    public function test_the_role_enum_describes_which_surfaces_a_role_reaches(): void
    {
        $this->assertTrue(UserRole::Admin->hasPanelAccess());
        $this->assertFalse(UserRole::Admin->hasAppAccess());
        $this->assertFalse(UserRole::Mr->hasPanelAccess());
        $this->assertTrue(UserRole::Mr->hasAppAccess());
        $this->assertTrue(UserRole::Telecaller->hasPanelAccess());
        $this->assertTrue(UserRole::Telecaller->hasAppAccess());
    }
}
