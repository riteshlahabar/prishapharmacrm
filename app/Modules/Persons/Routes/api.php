<?php

use App\Modules\Persons\Http\Controllers\Api\PersonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Persons — shared app endpoint (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| Both apps read this one, so it is gated on *either* app ability rather than
| a single role: the controller scopes the rows by the caller's role.
*/

Route::middleware(['api.auth', 'abilities:role:mr,role:telecaller'])
    ->get('persons', [PersonController::class, 'index'])
    ->name('api.v1.persons.index');
