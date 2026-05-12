<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\AuthController;

Route::middleware('throttle:auth')->prefix('auth')->group(function () {
    Route::post('check-user', [AuthController::class, 'checkUser']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::middleware('jwt.auth')->prefix('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\ProfileController;

Route::middleware('jwt.auth')->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'getUser']);
    Route::post('update', [ProfileController::class, 'updateProfile']);
});

/*
|--------------------------------------------------------------------------
| SYSTEM (USER AREA)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\System\CartController;
use App\Http\Controllers\API\System\FavoriteController;
use App\Http\Controllers\API\System\OrderController;
use App\Http\Controllers\API\System\ProductController;
use App\Http\Controllers\API\System\StoreController;

Route::middleware('jwt.auth')->group(function () {

    /*
    |---------------- Cart ----------------
    */
    
    Route::prefix('cart')->group(function () {
        Route::post('add', [CartController::class, 'add']);
        Route::post('update-quantity/{id}', [CartController::class, 'updateQuantity']);
        Route::delete('remove/{id}', [CartController::class, 'remove']);
        Route::get('view', [CartController::class, 'show']);
    });

    /*
    |---------------- Favorites ----------------
    */
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('add', [FavoriteController::class, 'store']);
        Route::delete('remove/{id}', [FavoriteController::class, 'destroy']);
        Route::get('check/{productId}', [FavoriteController::class, 'check']);
    });

    /*
    |---------------- ORDERS (IMPORTANT RATE LIMITING) ----------------
    */
    Route::prefix('orders')
        ->middleware('throttle:orders')
        ->group(function () {

            Route::get('/', [OrderController::class, 'index']);
            Route::post('place', [OrderController::class, 'store']);
            Route::get('{id}', [OrderController::class, 'show']);

            Route::post('cancel/{id}', [OrderController::class, 'cancel']);
            Route::post('update-product-quantity/{orderId}', [OrderController::class, 'updateProductQuantity']);
        });
});

/*
|--------------------------------------------------------------------------
| PUBLIC DATA
|--------------------------------------------------------------------------
*/

Route::apiResource('stores', StoreController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;

/*
|---------------- Admin Auth ----------------
*/

Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('register', [AdminAuthController::class, 'register']);

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('profile', [AdminAuthController::class, 'me']);
    });
});

/*
|---------------- Admin Panel ----------------
*/

Route::prefix('admin')
    ->middleware('auth:admin')
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('stores', AdminStoreController::class);
        Route::apiResource('products', AdminProductController::class);

        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'getAllOrders']);
            Route::get('{id}', [AdminOrderController::class, 'show']);
            Route::post('handle/{orderId}', [AdminOrderController::class, 'handleOrder']);
        });
    });

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\SearchController;

Route::get('search', [SearchController::class, 'search']);