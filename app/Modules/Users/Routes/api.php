<?php

use App\Modules\Users\Http\Controllers\Api\AssigneeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Users — assignment pickers (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| Each app asks for the *other* role, so each route is gated on the ability
| of the app that needs it: an MR picks a telecaller, a telecaller picks an
| MR. Asking for your own role is a 403, not an empty list.
*/

Route::middleware('api.auth')->prefix('assignees')->name('api.v1.assignees.')->group(function () {
    Route::middleware('ability:role:telecaller')
        ->get('mrs', [AssigneeController::class, 'mrs'])
        ->name('mrs');

    Route::middleware('ability:role:mr')
        ->get('telecallers', [AssigneeController::class, 'telecallers'])
        ->name('telecallers');
});
