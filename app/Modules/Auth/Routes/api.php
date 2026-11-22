<?php

use App\Modules\Auth\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth module — /api/v1/auth (both Flutter apps)
|--------------------------------------------------------------------------
| The `api/v1` prefix comes from App\Core\Providers\ModuleServiceProvider.
| No registration endpoint: the admin creates every account.
*/

Route::prefix('auth')->name('api.v1.auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    Route::middleware('api.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
    });
});
