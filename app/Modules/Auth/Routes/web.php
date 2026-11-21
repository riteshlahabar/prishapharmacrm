<?php

use App\Modules\Auth\Http\Controllers\Web\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth module — panel session routes
|--------------------------------------------------------------------------
| The only routes in the panel that are *not* behind `auth`. Throttled because
| they take credentials: 6 attempts a minute per IP + email.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');

    Route::post('login', [LoginController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.post');
});

Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
