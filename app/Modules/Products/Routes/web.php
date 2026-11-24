<?php

use App\Modules\Products\Http\Controllers\Web\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Products module — panel routes
|--------------------------------------------------------------------------
| No `show`: a product is four fields, and the edit form shows all of them.
*/

Route::middleware('auth')->group(function () {
    Route::resource('products', ProductController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:products.view')
        ->middlewareFor(['create', 'store'], 'permission:products.create')
        ->middlewareFor(['edit', 'update'], 'permission:products.update')
        ->middlewareFor('destroy', 'permission:products.delete');

    Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])
        ->middleware('permission:products.update')
        ->name('products.toggle');
});
