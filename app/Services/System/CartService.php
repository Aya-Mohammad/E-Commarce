<?php
 
namespace App\Services\System;
 
use App\Models\Cart;
use App\Models\Favourite;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
 
class CartService
{
    /**
     * Cache Key Strategy (6)
     */
    private function cartCacheKey(int $userId, int $page = 1): string
    {
        return "cart:user:{$userId}:page:{$page}";
    }
 
    public function add(array $data)
    {
        $userId       = auth()->id();
        $maxPerProduct = 100;
 
        /**
         * ACID Transaction (8)
         */
        return DB::transaction(function () use ($data, $userId, $maxPerProduct) {
 
            /**
             * Pessimistic Locking (7) — Critical Section
             */
            $product = Product::where('id', $data['product_id'])
                ->lockForUpdate()
                ->first();
 
            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }
 
            /**
             * Pessimistic Locking
             */
            $cartItem   = Cart::where('user_id', $userId)
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();
 
            $currentQty = $cartItem?->quantity ?? 0;
            $newQty     = $currentQty + $data['quantity'];
 
            /**
             * Capacity Control (2)
             */
            if ($newQty > $maxPerProduct) {
                return [
                    'error'  => "Maximum allowed quantity per product is {$maxPerProduct}",
                    'status' => 422,
                ];
            }
 
            /**
             * Race Condition Check (1)
             */
            if ($product->quantity < $newQty) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }
 
            $result = Cart::updateOrCreate(
                ['user_id'    => $userId, 'product_id' => $data['product_id']],
                ['quantity'   => $newQty]
            );
 
            /**
             * Cache Invalidation (6)
             */
            Cache::forget("product:{$product->id}");
            Cache::forget("product_stock:{$product->id}");
            Cache::forget($this->cartCacheKey($userId));
 
            return $result;
        });
    }
 
    public function remove($id)
    {
        $userId = auth()->id();
 
        /**
         * ACID Transaction (8) + Pessimistic Locking (7)
         */
        return DB::transaction(function () use ($id, $userId) {
 
            $cart = Cart::where('id', $id)
                ->where('user_id', $userId)
                ->lockForUpdate() // (7)
                ->first();
 
            // Data Integrity (1)
            if (!$cart) {
                return ['error' => 'Cart item not found', 'status' => 404];
            }
 
            $cart->delete();
 
            // Cache Invalidation (6)
            Cache::forget($this->cartCacheKey($userId));
 
            return true;
        });
    }
 
    public function updateQuantity($id, $quantity)
    {
        // Capacity Control (2)
        if (!is_numeric($quantity) || (int) $quantity <= 0) {
            return ['error' => 'Quantity must be a positive number', 'status' => 422];
        }
 
        $quantity = (int) $quantity;
 
        /**
         * ACID Transaction (8)
         */
        return DB::transaction(function () use ($id, $quantity) {
 
            $userId = auth()->id();
 
            /**
             * Pessimistic Locking (7)
             */
            $cart = Cart::where('id', $id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
 
            if (!$cart) {
                return ['error' => 'Cart item not found', 'status' => 404];
            }
 
            $product = Product::where('id', $cart->product_id)
                ->lockForUpdate()
                ->first();
 
            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }
 
            /**
             * Race Condition Check (1)
             */
            if ($product->quantity < $quantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }
 
            $cart->update(['quantity' => $quantity]);
 
            // Cache Invalidation (6)
            Cache::forget($this->cartCacheKey($userId));
            Cache::forget("product:{$product->id}");
 
            return $cart->fresh('product');
        });
    }
 
    public function show()
    {
        $userId  = auth()->id();
        $perPage = 10;
        $page    = (int) request()->get('page', 1);
 
        /**
         * Distributed Caching (6)
         */
        $cacheKey = $this->cartCacheKey($userId, $page);
 
        /**
         * Resource Management (2) + Pagination
         */
        $cart = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $perPage) {
            return Cart::where('user_id', $userId)
                ->with('product')
                ->paginate($perPage);
        });
 
        return [
            'data' => $cart->getCollection()->map(function ($item) {
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
            ],
        ];
    }
 
    public function moveFavorite(array $data, $favoriteId)
    {
        /**
         * ACID Transaction (8)
         */
        return DB::transaction(function () use ($data, $favoriteId) {
 
            $userId = auth()->id();
 
            /**
             * Pessimistic Locking (7)
             */
            $favorite = Favourite::where('id', $favoriteId)
                ->where('user_id', $userId)
                ->lockForUpdate()
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
 
            // Capacity Control (2) + Data Integrity (1)
            $quantity = (int) $data['quantity'];
 
            if ($quantity <= 0) {
                return ['error' => 'Invalid quantity', 'status' => 422];
            }
 
            $cartItem      = Cart::where('user_id', $userId)
                ->where('product_id', $favorite->product_id)
                ->lockForUpdate()
                ->first();
 
            $targetQuantity = ($cartItem?->quantity ?? 0) + $quantity;
 
            // Race Condition Check (1)
            if ($product->quantity < $targetQuantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }
 
            // ACID (8) 
            $cartItem = Cart::updateOrCreate(
                ['user_id' => $userId, 'product_id' => $favorite->product_id],
                ['quantity' => $targetQuantity]
            );
 
            $favorite->delete();
 
            // Cache Invalidation (6) 
            Cache::forget($this->cartCacheKey($userId));
            Cache::forget("favorites:user:{$userId}");
            Cache::forget("product:{$favorite->product_id}");
 
            return $cartItem;
        });
    }
}