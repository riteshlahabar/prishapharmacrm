<?php

use App\Modules\Visits\Http\Controllers\Web\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Visits module — panel routes
|--------------------------------------------------------------------------
| No create or edit: visits are filed by the MR app. Delete exists for
| clearing a mistaken entry.
*/

Route::middleware('auth')->group(function () {
    Route::get('visits', [VisitController::class, 'index'])
        ->middleware('permission:visits.view')->name('visits.index');

    Route::get('visits/{visit}', [VisitController::class, 'show'])
        ->middleware('permission:visits.view')->name('visits.show');

    Route::delete('visits/{visit}', [VisitController::class, 'destroy'])
        ->middleware('permission:visits.delete')->name('visits.destroy');
});
