<?php

use App\Modules\Calls\Http\Controllers\Web\CallLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Calls module — panel routes
|--------------------------------------------------------------------------
| No create or edit: calls are logged by the telecaller app. Delete exists
| for clearing a mistaken entry.
*/

Route::middleware('auth')->group(function () {
    Route::get('calls', [CallLogController::class, 'index'])
        ->middleware('permission:calls.view')->name('calls.index');

    Route::get('calls/{callLog}', [CallLogController::class, 'show'])
        ->middleware('permission:calls.view')->name('calls.show');

    Route::delete('calls/{callLog}', [CallLogController::class, 'destroy'])
        ->middleware('permission:calls.delete')->name('calls.destroy');
});
