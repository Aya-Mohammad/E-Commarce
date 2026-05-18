<?php
 
namespace App\Services\System;
 
use App\Models\Favourite;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
 
class FavoriteService
{
    /**
     * Cache Key Strategy (6)
     */
    private function favCacheKey(int $userId, int $page = 1): string
    {
        return "favorites:user:{$userId}:page:{$page}";
    }
 
    public function getFavorites(int $perPage = 15)
    {
        $userId = auth()->id();
        $page   = (int) request()->get('page', 1);
 
        /**
         * Distributed Caching (6)
         * Resource Management (2)
         */
        return Cache::remember($this->favCacheKey($userId, $page), now()->addMinutes(30), function () use ($userId, $perPage) {
            return Product::whereHas('favourites', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->with('image')
                ->paginate($perPage);
        });
    }
 
    public function isFavorite($productId): bool
    {
        // Capacity Control (2) 
        if (!is_numeric($productId) || (int) $productId <= 0) {
            return false;
        }
 
        $userId = auth()->id();
 
        /**
         * Distributed Caching (6)
         */
        $cacheKey = "is_favorite:user:{$userId}:product:{$productId}";
 
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId, $productId) {
            return Favourite::where('user_id', $userId)
                ->where('product_id', (int) $productId)
                ->exists();
        });
    }
 
    public function addToFavorite($productId): array
    {
        // Capacity Control (2)
        if (!is_numeric($productId) || (int) $productId <= 0) {
            return ['status' => false, 'message' => 'Invalid product ID', 'code' => 422];
        }
 
        $productId = (int) $productId;
        $userId    = auth()->id();
 
        /**
         * ACID Transaction (8)
         */
        return DB::transaction(function () use ($productId, $userId) {
 
            /**
             * Pessimistic Locking (7)
             */
            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->first();
 
            if (!$product) {
                return ['status' => false, 'message' => 'Product not found', 'code' => 404];
            }
 
            // (Concurrent Access & Data Integrity) (1)
            $fav = Favourite::firstOrCreate([
                'user_id'    => $userId,
                'product_id' => $productId,
            ]);
 
            /**
             * Cache Invalidation (6)
             */
            Cache::forget($this->favCacheKey($userId));
            Cache::forget("is_favorite:user:{$userId}:product:{$productId}");
 
            return [
                'status'  => true,
                'message' => 'Product added to favorites',
                'code'    => 201,
                'data'    => ['favorite' => $fav, 'product' => $product],
            ];
        });
    }
 
    public function removeFromFavorite($productId): array
    {
        // Capacity Control (2)
        if (!is_numeric($productId) || (int) $productId <= 0) {
            return ['status' => false, 'message' => 'Invalid product ID', 'code' => 422];
        }
 
        $productId = (int) $productId;
        $userId    = auth()->id();
 
        /**
         * ACID Transaction (8) + Pessimistic Locking (7)
         */
        return DB::transaction(function () use ($productId, $userId) {
 
            $fav = Favourite::where('user_id', $userId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();
 
            if (!$fav) {
                return ['status' => false, 'message' => 'Product not in favorites', 'code' => 404];
            }
 
            $fav->delete();
 
            // Cache Invalidation (6)
            Cache::forget($this->favCacheKey($userId));
            Cache::forget("is_favorite:user:{$userId}:product:{$productId}");
 
            return ['status' => true, 'message' => 'Product removed from favorites', 'code' => 200];
        });
    }
}