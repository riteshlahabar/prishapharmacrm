<?php

use App\Modules\Users\Http\Controllers\Web\MrController;
use App\Modules\Users\Http\Controllers\Web\TelecallerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Users module — panel routes
|--------------------------------------------------------------------------
| Both resources name their parameter `user` so the shared controller, the
| shared views and UpdateUserRequest's unique-ignore rule work for either.
| Capability checks are spatie permissions from
| App\Modules\Access\Support\PermissionCatalog.
*/

Route::middleware(['auth'])->group(function () {
    Route::resource('mrs', MrController::class)
        ->parameters(['mrs' => 'user'])
        ->middlewareFor(['index', 'show'], 'permission:mrs.view')
        ->middlewareFor(['create', 'store'], 'permission:mrs.create')
        ->middlewareFor(['edit', 'update'], 'permission:mrs.update')
        ->middlewareFor('destroy', 'permission:mrs.delete');

    Route::patch('mrs/{user}/toggle', [MrController::class, 'toggle'])
        ->middleware('permission:mrs.update')
        ->name('mrs.toggle');

    Route::resource('telecallers', TelecallerController::class)
        ->parameters(['telecallers' => 'user'])
        ->middlewareFor(['index', 'show'], 'permission:telecallers.view')
        ->middlewareFor(['create', 'store'], 'permission:telecallers.create')
        ->middlewareFor(['edit', 'update'], 'permission:telecallers.update')
        ->middlewareFor('destroy', 'permission:telecallers.delete');

    Route::patch('telecallers/{user}/toggle', [TelecallerController::class, 'toggle'])
        ->middleware('permission:telecallers.update')
        ->name('telecallers.toggle');
});
