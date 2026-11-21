<?php

namespace App\Modules\Auth\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Exceptions\AuthenticationFailed;
use App\Modules\Auth\Http\Requests\PanelLoginRequest;
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel session login. Rules live in AuthService; this only renders the form
 * and turns a failed sign-in into a message on it.
 */
class LoginController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function show(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth::login');
    }

    public function login(PanelLoginRequest $request): RedirectResponse
    {
        try {
            $this->auth->authenticatePanel(
                $request->email(),
                $request->string('password')->value(),
                $request->boolean('remember'),
            );
        } catch (AuthenticationFailed $e) {
            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->onlyInput('email');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logoutPanel();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }
}
