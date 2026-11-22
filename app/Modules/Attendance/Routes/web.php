<?php

use App\Modules\Attendance\Http\Controllers\Web\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance module — panel routes
|--------------------------------------------------------------------------
| Report only: rows come from the MR app, so there is no create or edit.
*/

Route::middleware(['auth', 'permission:attendance.view'])->group(function () {
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
});
