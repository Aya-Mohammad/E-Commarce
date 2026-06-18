<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\AuthController;

Route::middleware('throttle:auth', 'performance.monitor')->prefix('auth')->group(function () {
    Route::post('check-user', [AuthController::class, 'checkUser']);
    Route::post('login',      [AuthController::class, 'login']);
    Route::post('register',   [AuthController::class, 'register']);
});

Route::middleware('jwt.auth')->prefix('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});


/*
|--------------------------------------------------------------------------
| SYSTEM (USER AREA)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\StoreController;

Route::middleware('jwt.auth')->group(function () {

    Route::prefix('cart')
        ->middleware('performance.monitor')
        ->group(function () {
        Route::post('add',                    [CartController::class, 'add']);
        Route::post('update-quantity/{id}',   [CartController::class, 'updateQuantity']);
        Route::delete('remove/{id}',          [CartController::class, 'remove']);
        Route::get('view',                    [CartController::class, 'show']);
    });

    Route::prefix('orders')
        ->middleware('throttle:orders', 'performance.monitor')
        ->group(function () {
            Route::get('/',      [OrderController::class, 'index']);
            Route::post('place', [OrderController::class, 'store']);
        });
});

/*
|--------------------------------------------------------------------------
| PUBLIC DATA
|--------------------------------------------------------------------------
*/

Route::apiResource('stores', StoreController::class)->only(['index', 'show']);

Route::middleware('performance.monitor')->group(function () {
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
});

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\SearchController;

Route::middleware('performance.monitor')->group(function () {
    Route::get('/search', [SearchController::class, 'search']);
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES — NFR#2 (Rate Limiting) + NFR#3 (Async) + NFR#4 (Batch)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\AuthController    as AdminAuthController;
use App\Http\Controllers\Admin\OrderController   as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StoreController   as AdminStoreController;
use App\Http\Controllers\Admin\SalesReportController;

Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
});

Route::prefix('admin')
    ->middleware('auth:admin')
    ->group(function () {

    Route::post('orders/{orderId}/handle', [AdminOrderController::class, 'handleOrder']);

    Route::post('products',        [AdminProductController::class, 'store']);
    Route::delete('products/{id}', [AdminProductController::class, 'destroy']);

    Route::post('stores',        [AdminStoreController::class, 'store']);
    Route::delete('stores/{id}', [AdminStoreController::class, 'destroy']);

    Route::get('sales-reports',           [SalesReportController::class, 'index']);
    Route::get('sales-reports/{date}',    [SalesReportController::class, 'show']);
    Route::post('sales-reports/trigger',  [SalesReportController::class, 'trigger']);
});