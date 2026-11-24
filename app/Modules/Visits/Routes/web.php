<?php

use App\Modules\Visits\Http\Controllers\Web\VisitController;
use App\Modules\Visits\Http\Controllers\Web\VisitPhotoController;
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

    // The shopfront photo. Behind the same permission as the visit page, and
    // scoped to the same territory by the controller — the file itself sits
    // on a private disk with no public URL.
    Route::get('visits/{visit}/photo', VisitPhotoController::class)
        ->middleware('permission:visits.view')->name('visits.photo');

    Route::delete('visits/{visit}', [VisitController::class, 'destroy'])
        ->middleware('permission:visits.delete')->name('visits.destroy');
});
