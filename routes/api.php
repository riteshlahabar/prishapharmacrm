<?php

use App\Modules\Auth\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
| New work goes under /api/v1 and is registered by each module
| (app/Modules/<Name>/Routes/api.php) — see docs/api/.
|
| What remains here are the unversioned legacy auth routes the two shipped
| Flutter builds still call (both use baseUrl ".../api" plus "/auth/login").
| They reach the module controller, so there is one implementation and one
| envelope, and they go away once both apps move to /api/v1.
|
| The legacy customers, products, orders, reports and dashboard endpoints
| went with the tables behind them in Phase 2.
*/

Route::prefix('auth')->name('api.legacy.auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    Route::middleware('api.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});
