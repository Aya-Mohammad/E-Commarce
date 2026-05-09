<?php

namespace App\Services\System;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CartService
{

    private const CART_CACHE_KEY = 'cart:user:%d:page:%d';

    private function cartCacheKey(int $userId, int $page = 1): string
    {
        return sprintf(self::CART_CACHE_KEY, $userId, $page);
    }

    public function add(array $data)
    {
        $userId = auth()->id();
        $maxPerProduct = 100;

        // (8) Transaction Integrity / ACID
        return DB::transaction(function () use ($data, $userId, $maxPerProduct) {

            // (7) Concurrency Control - Pessimistic Locking
            $product = Product::where('id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return [
                    'error' => 'Product not found',
                    'status' => 404
                ];
            }

            // (1) Race Condition Prevention + Thread Safety
            $cartItem = Cart::where('user_id', $userId)
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            $currentQty = $cartItem?->quantity ?? 0;
            $newQty = $currentQty + $data['quantity'];

            // (2) Resource Management & Capacity Control
            if ($newQty > $maxPerProduct) {
                return [
                    'error' => "Maximum allowed quantity per product is {$maxPerProduct}",
                    'status' => 422
                ];
            }

            if ($product->quantity < $newQty) {
                return [
                    'error' => 'Not enough stock',
                    'status' => 422
                ];
            }

            $result = Cart::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $data['product_id'],
                ],
                [
                    'quantity' => $newQty,
                ]
            );

            // (6) Distributed Caching + Cache Invalidation
            Cache::forget("product:{$product->id}");
            Cache::forget("product_stock:{$product->id}");

            // Unified cart cache invalidation
            Cache::forget($this->cartCacheKey($userId));

            return $result;
        });
    }

    public function remove($id)
    {
        $userId = auth()->id();

        // (8) Transaction Integrity (ACID)
        return DB::transaction(function () use ($id, $userId) {

            // (7) Concurrency Control - Pessimistic Locking
            $cart = Cart::where('id', $id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            // (1) Data Integrity
            if (!$cart) {
                return [
                    'error' => 'Cart item not found',
                    'status' => 404
                ];
            }

            $cart->delete();

            // (6) Distributed Caching Strategy
            Cache::forget($this->cartCacheKey($userId));

            return true;
        });
    }
    public function updateQuantity($id, $quantity)
    {
        // (8) Transaction Integrity / ACID
        return DB::transaction(function () use ($id, $quantity) {

            $cart = Cart::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$cart) {
                return ['error' => 'Cart item not found', 'status' => 404];
            }

            // (7) Concurrency Control (Pessimistic Locking)
            $product = Product::where('id', $cart->product_id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }

            // (1) Data Integrity & Validation
            if ($quantity <= 0) {
                return ['error' => 'Quantity must be greater than 0', 'status' => 422];
            }

            // (1) Concurrent Access & Data Integrity
            if ($product->quantity < $quantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }

            $cart->update([
                'quantity' => $quantity
            ]);

            // NFR (6) Distributed Caching Strategy (Cache Invalidation)
            Cache::forget("cart_user_" . auth()->id());
            Cache::forget("product_" . $product->id);

            return $cart->fresh('product');
        });
    }

    public function show()
    {
        $userId = auth()->id();
        $perPage = 10;
        $page = request()->get('page', 1);

        // (6) Distributed Caching (Redis)
        $cacheKey = "cart:user:{$userId}:page:{$page}";

        $cart = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $perPage) {

            return Cart::where('user_id', $userId)
                ->with('product')

                // (1) Concurrent Access & Data Integrity
                // (2) Resource Management & Capacity Control
                ->paginate($perPage);
        });

        return [
            'data' => collect($cart->items())->map(function ($item) {

                return [
                    'id'           => $item->id,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name ?? 'Product unavailable',
                    'quantity'     => $item->quantity,
                    'price'        => $item->product->price ?? null,
                ];
            }),

            'pagination' => [
                'current_page' => $cart->currentPage(),
                'last_page'    => $cart->lastPage(),
                'total'        => $cart->total(),
            ]
        ];
    }

    public function moveFavorite(array $data, $favoriteId)
    {
        return DB::transaction(function () use ($data, $favoriteId) {

            // (7) Concurrency Control - Pessimistic Locking
            $favorite = FavoriteOfProduct::where('id', $favoriteId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$favorite) {
                return ['error' => 'Favorite item not found', 'status' => 404];
            }

            $product = Product::where('id', $favorite->product_id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }

            // (1) Data Integrity 
            $quantity = (int) $data['quantity'];

            if ($quantity <= 0) {
                return ['error' => 'Invalid quantity', 'status' => 422];
            }

            $cartItem = Cart::where('user_id', $favorite->user_id)
                ->where('product_id', $favorite->product_id)
                ->first();

            $targetQuantity = ($cartItem?->quantity ?? 0) + $quantity;

            if ($product->quantity < $targetQuantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }

            // (8) Transaction Integrity (ACID)
            if ($cartItem) {
                $cartItem->update(['quantity' => $targetQuantity]);
            } else {
                $cartItem = Cart::create([
                    'user_id'    => $favorite->user_id,
                    'product_id' => $favorite->product_id,
                    'quantity'   => $quantity,
                ]);
            }

            $favorite->delete();

            // (6) Distributed Caching Strategy
            Cache::forget("cart_user_" . $favorite->user_id);
            Cache::forget("favorites_user_" . $favorite->user_id);
            Cache::forget("product_" . $favorite->product_id);

            return $cartItem;
        });
    }
}