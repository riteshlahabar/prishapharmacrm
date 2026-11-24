<?php

use App\Modules\Products\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Products — shared app endpoint (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| Both apps read this one, so it is gated on *either* app ability rather than
| a single role, exactly as /persons is.
*/

Route::middleware(['api.auth', 'abilities:role:mr,role:telecaller'])
    ->get('products', [ProductController::class, 'index'])
    ->name('api.v1.products.index');
