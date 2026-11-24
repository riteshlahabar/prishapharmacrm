<?php

use App\Modules\Persons\Http\Controllers\Api\PersonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Persons — shared app endpoints (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| Both apps use these, so they are gated on *either* app ability rather than
| a single role: the controller scopes the rows by the caller's role, and the
| request decides which counterpart the body must name.
|
| GET  persons            the territory list — the telecaller's call queue
| GET  persons?mine=1     My Clients: the rows this caller is an assignee on
| POST persons            Add Client; the caller's own side comes from the token
*/

Route::middleware(['api.auth', 'abilities:role:mr,role:telecaller'])->group(function () {
    Route::get('persons', [PersonController::class, 'index'])->name('api.v1.persons.index');
    Route::post('persons', [PersonController::class, 'store'])->name('api.v1.persons.store');
});
