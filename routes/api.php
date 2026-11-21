<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\ReportController as ApiReportController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/register', [ApiAuthController::class, 'register']);
    Route::post('/logout', [ApiAuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [ApiAuthController::class, 'user'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [ApiDashboardController::class, 'index']);

    Route::prefix('customers')->group(function () {
        Route::get('/', [ApiCustomerController::class, 'index']);
        Route::get('/search', [ApiCustomerController::class, 'search']);
        Route::get('/{id}', [ApiCustomerController::class, 'show']);
        Route::post('/', [ApiCustomerController::class, 'store']);
        Route::put('/{id}', [ApiCustomerController::class, 'update']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ApiProductController::class, 'index']);
        Route::get('/search', [ApiProductController::class, 'search']);
        Route::get('/{id}', [ApiProductController::class, 'show']);
        Route::post('/', [ApiProductController::class, 'store']);
        Route::put('/{id}', [ApiProductController::class, 'update']);
    });

    Route::prefix('orders')->group(function () {
        Route::get('/', [ApiOrderController::class, 'index']);
        Route::get('/{id}', [ApiOrderController::class, 'show']);
        Route::post('/', [ApiOrderController::class, 'store']);
        Route::put('/{id}', [ApiOrderController::class, 'update']);
        Route::delete('/{id}', [ApiOrderController::class, 'destroy']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ApiReportController::class, 'salesReport']);
        Route::get('/inventory', [ApiReportController::class, 'inventoryReport']);
    });
});
