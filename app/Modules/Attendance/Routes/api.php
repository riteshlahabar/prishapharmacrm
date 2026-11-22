<?php

use App\Modules\Attendance\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance — MR app endpoints (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| `ability:role:mr` refuses a token minted for the telecaller app, even
| though it authenticates fine. Attendance belongs to field reps.
*/

Route::middleware(['api.auth', 'ability:role:mr'])
    ->prefix('attendance')
    ->name('api.v1.attendance.')
    ->group(function () {
        Route::get('today', [AttendanceController::class, 'today'])->name('today');
        Route::get('history', [AttendanceController::class, 'history'])->name('history');
        Route::post('check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
    });
