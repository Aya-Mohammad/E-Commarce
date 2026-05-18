<?php
 
namespace App\Services\System;
 
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
 
class ProductService
{
    /**
     * Cache Key Strategy (6)
     */
    private function productListCacheKey(int $page): string
    {
        return "products:page:{$page}";
    }
 
    private function productDetailCacheKey(int $id): string
    {
        return "product:detail:{$id}";
    }
 
    public function index(int $perPage = 15)
    {
        $page = (int) request()->get('page', 1);
 
        /**
         * Distributed Caching (6)
         * Resource Management (2)
         */
        return Cache::remember($this->productListCacheKey($page), now()->addHour(), function () use ($perPage) {
            return Product::with('image')->paginate($perPage);
        });
    }
 
    public function show($id)
    {
        // Capacity Control (2)
        if (!is_numeric($id) || (int) $id <= 0) {
            return null;
        }
 
        $id = (int) $id;
 
        /**
         * Distributed Caching (6)
         */
        return Cache::remember($this->productDetailCacheKey($id), now()->addHours(24), function () use ($id) {
            return Product::with('image')->findOrFail($id);
        });
    }
 
    /**
     * Cache Invalidation (6)
     */
    public function invalidateProductCache(int $productId): void
    {
        Cache::forget($this->productDetailCacheKey($productId));
 
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget($this->productListCacheKey($page));
        }
    }
}