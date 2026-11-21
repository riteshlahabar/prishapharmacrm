<?php

use App\Modules\Access\Http\Controllers\Web\RolePermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Access module — panel routes
|--------------------------------------------------------------------------
| `access.manage` is deliberately outside PermissionCatalog::grantable(), so
| only the admin role reaches these screens and a telecaller can never widen
| its own rights.
*/

Route::middleware(['auth', 'permission:access.manage'])
    ->prefix('access')
    ->name('access.')
    ->group(function () {
        Route::get('permissions', [RolePermissionController::class, 'index'])
            ->name('permissions.index');

        Route::get('permissions/{user}', [RolePermissionController::class, 'edit'])
            ->name('permissions.edit');

        Route::put('permissions/{user}', [RolePermissionController::class, 'update'])
            ->name('permissions.update');
    });
