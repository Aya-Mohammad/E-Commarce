<?php

use App\Http\Controllers\API\AuthController;
/*
|--------------------------------------------------------------------------
| Auth (User)
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\API\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('loginWithPassword', [AuthController::class, 'loginWithPassword']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::middleware(['jwt.auth'])->prefix('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Profile (User)
|--------------------------------------------------------------------------
*/
Route::middleware(['jwt.auth'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'getUser']);
    Route::post('update', [ProfileController::class, 'updateProfile']);
});

/*
|--------------------------------------------------------------------------
| System APIs (User Area)
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\API\System\CartController as SystemCartController;
use App\Http\Controllers\API\System\FavoriteController as SystemFavoriteController;
use App\Http\Controllers\API\System\OrderController as SystemOrderController;
use App\Http\Controllers\API\System\ProductController as SystemProductController;
use App\Http\Controllers\API\System\StoreController as SystemStoreController;

/**
 * Protected user actions
 */
Route::middleware(['jwt.auth'])->group(function () {

    /*
    |-------------------------
    | Cart
    |-------------------------
    */
    Route::prefix('cart')->group(function () {
        Route::post('add', [SystemCartController::class, 'add']);
        Route::post('update-quantity/{id}', [SystemCartController::class, 'updateQuantity']);
        Route::delete('remove/{id}', [SystemCartController::class, 'remove']);
        Route::get('view', [SystemCartController::class, 'show']);
    });

    /*
    |-------------------------
    | Favorites
    |-------------------------
    */
    Route::prefix('favorites')->group(function () {
        Route::get('/', [SystemFavoriteController::class, 'index']);
        Route::post('add', [SystemFavoriteController::class, 'store']);
        Route::delete('remove/{id}', [SystemFavoriteController::class, 'destroy']);
        Route::get('check-favorite/{productId}', [SystemFavoriteController::class, 'check']);
    });

    /*
    |-------------------------
    | Orders (User)
    |-------------------------
    */
    Route::prefix('orders')->group(function () {
        Route::get('/', [SystemOrderController::class, 'index']);
        Route::post('place', [SystemOrderController::class, 'store']);
        Route::get('{id}', [SystemOrderController::class, 'show']);
        Route::post('cancel/{id}', [SystemOrderController::class, 'cancel']);
        Route::post('update-product-quantity/{orderId}', [SystemOrderController::class, 'updateProductQuantity']);
    });
});

/*
|--------------------------------------------------------------------------
| Public System Data (Read-only)
|--------------------------------------------------------------------------
*/
Route::apiResource('stores', SystemStoreController::class)->only(['index', 'show']);
Route::apiResource('products', SystemProductController::class)->only(['index', 'show']);

/*
|--------------------------------------------------------------------------
| Admin Auth
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\AdminAuthController;

Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('auth:admin');
    Route::get('profile', [AdminAuthController::class, 'me'])->middleware('auth:admin');
});

/*
|--------------------------------------------------------------------------
| Admin Panel
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;

Route::prefix('admin')
    ->middleware(['auth:admin'])
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('stores', AdminStoreController::class);
        Route::apiResource('products', AdminProductController::class);
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'getAllOrders']);
            Route::get('{id}', [AdminOrderController::class, 'show']);
        });
    });

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\API\SearchController;

Route::get('search', [SearchController::class, 'search']);
