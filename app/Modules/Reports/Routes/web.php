<?php

use App\Modules\Reports\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reports module — panel routes
|--------------------------------------------------------------------------
| `reports.view` reaches the screens; downloading needs `reports.export` on
| top, because a CSV leaves the panel and the audit trail behind it. Both are
| in PermissionCatalog::grantable(), and every report is clamped to the
| viewer's territory inside ReportService.
|
| `export` is declared before `{report}` so the literal segment is not
| swallowed by the parameter.
*/

Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')->name('index');

    Route::get('{report}/export', [ReportController::class, 'export'])
        ->middleware('permission:reports.export')->name('export');

    Route::get('{report}', [ReportController::class, 'show'])
        ->middleware('permission:reports.view')->name('show');
});
