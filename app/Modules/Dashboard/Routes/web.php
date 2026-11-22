<?php

use App\Modules\Dashboard\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard — the panel landing screen
|--------------------------------------------------------------------------
| Phase 7 moved this out of routes/web.php, so the module owns its own route
| as every other module does. The name stays `dashboard`: the sidebar, the
| breadcrumbs and the post-login redirect all point at it.
*/

Route::middleware(['auth', 'permission:dashboard.view'])
    ->get('/', [DashboardController::class, 'index'])
    ->name('dashboard');
