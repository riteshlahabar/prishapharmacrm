<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel routes
|--------------------------------------------------------------------------
| Sign-in lives in the Auth module; everything else is registered by the
| module that owns it, through config/modules.php. What is left here is the
| dashboard — the one screen that belongs to no single module until Phase 7
| gives it one.
|
| Phase 2 retired the generic-CRM resources: customers became persons in the
| Persons module, and products, orders and their reports were dropped from
| scope (docs/roadmap.md).
*/

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
});
