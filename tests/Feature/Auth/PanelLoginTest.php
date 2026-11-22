<?php

namespace Tests\Feature\Auth;

use App\Modules\Regions\Models\Region;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The Cuba panel's session login. `admin` and `telecaller` reach the panel;
 * `mr` is app-only (UserRole::hasPanelAccess).
 */
class PanelLoginTest extends TestCase
{
    public function test_the_login_form_is_the_one_public_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('login', false);
    }

    public function test_an_admin_signs_in_and_lands_on_the_dashboard(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_telecaller_may_sign_into_the_panel(): void
    {
        $telecaller = $this->telecaller([Region::factory()->create()]);

        $this->post('/login', ['email' => $telecaller->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($telecaller);
    }

    public function test_an_mr_is_sent_back_to_the_form_because_the_panel_is_not_their_surface(): void
    {
        $mr = $this->mr();

        $this->from('/login')
            ->post('/login', ['email' => $mr->email, 'password' => self::PASSWORD])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_wrong_password_keeps_the_email_and_reports_an_error(): void
    {
        $admin = $this->admin();

        $this->from('/login')
            ->post('/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $admin->email);

        $this->assertGuest();
    }

    public function test_a_deactivated_admin_cannot_sign_in(): void
    {
        $admin = $this->admin(['is_active' => false]);

        $this->from('/login')
            ->post('/login', ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_role_less_user_cannot_sign_in(): void
    {
        $orphan = User::factory()->create(['role' => null]);

        $this->from('/login')
            ->post('/login', ['email' => $orphan->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_credentials_are_required(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_an_authenticated_visitor_is_bounced_off_the_login_form(): void
    {
        $this->actingAs($this->admin())->get('/login')->assertRedirect(route('dashboard'));
    }

    public function test_logging_out_clears_the_session(): void
    {
        $this->actingAs($this->admin());

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_the_session_id_changes_on_login_so_a_fixed_session_cannot_be_reused(): void
    {
        $admin = $this->admin();

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => $admin->email, 'password' => self::PASSWORD]);

        $this->assertNotSame($before, session()->getId());
        $this->assertTrue(Auth::check());
    }

    public function test_panel_login_is_throttled(): void
    {
        $admin = $this->admin();

        // throttle:6,1 on the route.
        foreach (range(1, 6) as $ignored) {
            $this->from('/login')->post('/login', [
                'email' => $admin->email,
                'password' => 'wrong',
            ]);
        }

        $this->from('/login')
            ->post('/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_every_panel_route_sits_behind_auth(): void
    {
        foreach (['/', '/persons', '/regions', '/attendance', '/calls', '/messages'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }
}
