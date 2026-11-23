<?php

use App\Modules\Persons\Http\Controllers\Web\PersonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Persons module — panel routes
|--------------------------------------------------------------------------
| The panel calls them clients, so the URLs and route names do too; the
| module, the model, the table and the mobile API still say person
| (docs/modules/persons.md). `parameters()` keeps the route parameter named
| {person}, which is what the controller methods and StorePersonRequest's
| unique-ignore read.
|
| The permission names are unchanged: `persons.*` rows already exist in every
| deployment's database, and renaming them would need a data migration for no
| user-visible gain.
|
| A telecaller granted persons.view reaches the list, but PersonService
| clamps it to the regions they cover.
*/

Route::middleware('auth')->group(function () {
    Route::resource('clients', PersonController::class)
        ->parameters(['clients' => 'person'])
        ->middlewareFor(['index', 'show'], 'permission:persons.view')
        ->middlewareFor(['create', 'store'], 'permission:persons.create')
        ->middlewareFor(['edit', 'update'], 'permission:persons.update')
        ->middlewareFor('destroy', 'permission:persons.delete');

    Route::patch('clients/{person}/toggle', [PersonController::class, 'toggle'])
        ->middleware('permission:persons.update')
        ->name('clients.toggle');
});
