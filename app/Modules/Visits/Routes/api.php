<?php

use App\Modules\Visits\Http\Controllers\Api\VisitController;
use App\Modules\Visits\Http\Controllers\Api\VisitPhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Visits — MR app endpoints (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
*/

Route::middleware(['api.auth', 'ability:role:mr'])
    ->prefix('visits')
    ->name('api.v1.visits.')
    ->group(function () {
        Route::get('/', [VisitController::class, 'index'])->name('index');
        Route::post('/', [VisitController::class, 'store'])->name('store');
        // Correcting an entry the rep filed themselves. Author-only, which
        // is VisitPolicy's `amend` — not the panel's `visits.update`.
        Route::patch('{visit}', [VisitController::class, 'update'])->name('update');

        // The shopfront photo, for the app's visit detail screen. Still off
        // the private disk and still behind the token — the same scope the
        // panel's route has, just reached with a bearer instead of a session.
        Route::get('{visit}/photo', VisitPhotoController::class)->name('photo');
    });
