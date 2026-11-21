<?php

use App\Modules\Persons\Http\Controllers\Web\PersonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Persons module — panel routes
|--------------------------------------------------------------------------
| A telecaller granted persons.view reaches the list, but PersonService
| clamps it to the regions they cover.
*/

Route::middleware('auth')->group(function () {
    Route::resource('persons', PersonController::class)
        ->middlewareFor(['index', 'show'], 'permission:persons.view')
        ->middlewareFor(['create', 'store'], 'permission:persons.create')
        ->middlewareFor(['edit', 'update'], 'permission:persons.update')
        ->middlewareFor('destroy', 'permission:persons.delete');

    Route::patch('persons/{person}/toggle', [PersonController::class, 'toggle'])
        ->middleware('permission:persons.update')
        ->name('persons.toggle');
});
