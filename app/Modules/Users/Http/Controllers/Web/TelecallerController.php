<?php

namespace App\Modules\Users\Http\Controllers\Web;

use App\Modules\Users\Enums\UserRole;

/**
 * Admin CRUD for telecallers — users who sign into both the telecaller app and
 * the panel (with only the permissions the admin grants them; see the Access
 * module).
 */
class TelecallerController extends UserCrudController
{
    protected function role(): UserRole
    {
        return UserRole::Telecaller;
    }

    protected function routeName(): string
    {
        return 'telecallers';
    }

    protected function titles(): array
    {
        return [
            'singular' => 'Telecaller',
            'plural' => 'Telecallers',
        ];
    }
}
