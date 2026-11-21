<?php

use App\Modules\Regions\Http\Controllers\Web\RegionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Regions module — panel routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::resource('regions', RegionController::class)
        ->middlewareFor(['index', 'show'], 'permission:regions.view')
        ->middlewareFor(['create', 'store'], 'permission:regions.create')
        ->middlewareFor(['edit', 'update'], 'permission:regions.update')
        ->middlewareFor('destroy', 'permission:regions.delete');

    Route::patch('regions/{region}/toggle', [RegionController::class, 'toggle'])
        ->middleware('permission:regions.update')
        ->name('regions.toggle');
});
