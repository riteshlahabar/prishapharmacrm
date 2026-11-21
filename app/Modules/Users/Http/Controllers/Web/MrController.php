<?php

namespace App\Modules\Users\Http\Controllers\Web;

use App\Modules\Users\Enums\UserRole;

/**
 * Admin CRUD for medical representatives — the users who sign into the MR app.
 */
class MrController extends UserCrudController
{
    protected function role(): UserRole
    {
        return UserRole::Mr;
    }

    protected function routeName(): string
    {
        return 'mrs';
    }

    protected function titles(): array
    {
        return [
            'singular' => 'Medical Rep',
            'plural' => 'Medical Reps',
        ];
    }
}
