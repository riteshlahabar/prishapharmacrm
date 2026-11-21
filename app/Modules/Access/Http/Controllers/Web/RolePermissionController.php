<?php

namespace App\Modules\Access\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Requests\SyncPermissionsRequest;
use App\Modules\Access\Services\Contracts\PermissionServiceInterface;
use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin screen for telecaller rights: list the telecallers, then tick what one
 * of them may do in the panel. Admins are not listed — the admin role always
 * holds the full catalog and is re-synced by RoleSeeder.
 */
class RolePermissionController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $users,
        private readonly PermissionServiceInterface $permissions,
    ) {}

    public function index(Request $request): View
    {
        $filters = ['search' => $request->string('search')->trim()->value()];
        $telecallers = $this->users->paginateForRole(UserRole::Telecaller, $filters, 20);

        // Counted here, not in the view: Blade renders, it does not query.
        $counts = [];

        foreach ($telecallers as $telecaller) {
            $counts[$telecaller->id] = [
                'effective' => $this->permissions->effectiveFor($telecaller)->count(),
                'direct' => $this->permissions->directFor($telecaller)->count(),
            ];
        }

        return view('access::index', [
            'telecallers' => $telecallers,
            'filters' => $filters,
            'counts' => $counts,
        ]);
    }

    public function edit(int|string $user): View
    {
        $telecaller = $this->users->findForRole(UserRole::Telecaller, $user);

        return view('access::edit', [
            'user' => $telecaller,
            'groups' => PermissionCatalog::grantable(),
            'direct' => $this->permissions->directFor($telecaller)->all(),
            'fromRole' => $this->permissions->fromRoleFor($telecaller)->all(),
        ]);
    }

    public function update(SyncPermissionsRequest $request, int|string $user): RedirectResponse
    {
        $telecaller = $this->users->findForRole(UserRole::Telecaller, $user);

        $granted = $this->permissions->syncDirect($telecaller, $request->permissionNames());

        return redirect()
            ->route('access.permissions.edit', $telecaller->id)
            ->with('success', count($granted)." extra permission(s) saved for {$telecaller->name}.");
    }
}
