<?php

use App\Modules\Messages\Http\Controllers\Web\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Messages module — panel routes
|--------------------------------------------------------------------------
| Read-only plus delete: messages are written in the apps, not the panel.
*/

Route::middleware('auth')->group(function () {
    Route::get('messages', [MessageController::class, 'index'])
        ->middleware('permission:messages.view')->name('messages.index');

    Route::get('messages/{message}', [MessageController::class, 'show'])
        ->middleware('permission:messages.view')->name('messages.show');

    Route::delete('messages/{message}', [MessageController::class, 'destroy'])
        ->middleware('permission:messages.delete')->name('messages.destroy');
});
