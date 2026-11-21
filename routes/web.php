<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel routes
|--------------------------------------------------------------------------
| Sign-in itself lives in the Auth module (app/Modules/Auth/Routes/web.php);
| everything here is behind `auth`, as required by docs/conventions.md.
|
| MR and telecaller administration live in the Users module, and the permission
| grant screens in the Access module — both register their own routes through
| config/modules.php.
|
| The customers / products / orders / reports resources below are the legacy
| generic-CRM screens. They keep working behind `auth` until Phase 2 replaces
| customers with persons and drops products and orders, which is also when they
| gain permission middleware.
*/

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::resource('customers', CustomerController::class);
    Route::resource('products', ProductController::class);
    Route::resource('orders', OrderController::class);

    Route::get('/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
    Route::get('/reports/inventory', [ReportController::class, 'inventoryReport'])->name('reports.inventory');
});
