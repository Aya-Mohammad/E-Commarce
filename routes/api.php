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
        Route::delete('remove/{id}', [SystemCartController::class, 'remove']);
        Route::get('view', [SystemCartController::class, 'view']);
    });

    /*
    |-------------------------
    | Favorites
    |-------------------------
    */
    Route::prefix('favorites')->group(function () {
        Route::get('/', [SystemFavoriteController::class, 'index']);
        Route::post('add', [SystemFavoriteController::class, 'add']);
        Route::delete('remove/{id}', [SystemFavoriteController::class, 'remove']);
        Route::get('is-favorited/{productId}', [SystemFavoriteController::class, 'isFavorited']);
    });

    /*
    |-------------------------
    | Orders (User)
    |-------------------------
    */
    Route::apiResource('orders', SystemOrderController::class);
});

/*
|--------------------------------------------------------------------------
| Public System Data (Read-only)
|--------------------------------------------------------------------------
*/
Route::prefix('stores')->group(function () {
    Route::get('/', [SystemStoreController::class, 'index']);
    Route::get('{id}', [SystemStoreController::class, 'show']);
});

Route::prefix('products')->group(function () {
    Route::get('/', [SystemProductController::class, 'index']);
    Route::get('{id}', [SystemProductController::class, 'show']);
});

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
        Route::apiResource('orders', AdminOrderController::class);
    });

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\API\SearchController;

Route::get('search', [SearchController::class, 'search']);
