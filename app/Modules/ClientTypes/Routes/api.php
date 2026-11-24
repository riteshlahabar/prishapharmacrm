<?php

use App\Modules\ClientTypes\Http\Controllers\Api\ClientTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ClientTypes — shared picker (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| Both Add Client forms read this one, so it is gated on *either* app
| ability rather than a single role. The list is the same for everybody —
| there is nothing territory-specific about what kinds of client exist.
*/

Route::middleware(['api.auth', 'abilities:role:mr,role:telecaller'])
    ->get('client-types', [ClientTypeController::class, 'index'])
    ->name('api.v1.client-types.index');
