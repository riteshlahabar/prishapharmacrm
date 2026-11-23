<?php

use App\Modules\ClientTypes\Http\Controllers\Web\ClientTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ClientTypes module — panel routes
|--------------------------------------------------------------------------
| Its own prefix rather than /clients/types: the clients resource would match
| "types" as a client id, and route order between modules is not something a
| URL should depend on.
*/

Route::middleware('auth')->group(function () {
    Route::resource('client-types', ClientTypeController::class)
        ->parameters(['client-types' => 'client_type'])
        ->except('show')
        ->middlewareFor('index', 'permission:client_types.view')
        ->middlewareFor(['create', 'store'], 'permission:client_types.create')
        ->middlewareFor(['edit', 'update'], 'permission:client_types.update')
        ->middlewareFor('destroy', 'permission:client_types.delete');

    Route::patch('client-types/{client_type}/toggle', [ClientTypeController::class, 'toggle'])
        ->middleware('permission:client_types.update')
        ->name('client-types.toggle');
});
