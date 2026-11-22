<?php

use App\Modules\Visits\Http\Controllers\Api\VisitController;
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
    });
