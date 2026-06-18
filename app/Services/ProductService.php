<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 

class ProductService
{
    private function getProductsVersion(): int
    {
        return Cache::get('products_version', 1);
    }

    private function productListCacheKey(int $page): string
    {
        $version = $this->getProductsVersion();

        return "products:v{$version}:page:{$page}";
    }

    private function productDetailCacheKey(int $id): string
    {
        return "product:detail:{$id}";
    }

    public function index(int $perPage = 15)
    {
        $page = (int) request()->get('page', 1);
        return Cache::remember($this->productListCacheKey($page), now()->addHour(), function () use ($perPage) {
            return Product::with('image')->paginate($perPage);
        });
    }

    public function invalidateProductCache(int $productId): void
    {
        Cache::forget($this->productDetailCacheKey($productId));

        Cache::increment('products_version');
    }

    private function getLogChannel(): string
    {
        $testType = request()->header('X-Test-Type');
        $operation = request()->header('X-Operation');

        if ($testType === 'combined_100') {
            return match ($operation) {
                'product_show' => 'combined_product_show',
                default        => 'combined',
            };
        }

        return 'product_show';
    }

    public function show($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) { return ['error' => 'Invalid Product ID', 'status' => 422]; }
        if (config('app.strict_nfr_mode', false)) { return $this->optimizedShow($id); }
        return $this->legacyShow($id);
    }

    private function legacyShow($id)
    {
        $channel = $this->getLogChannel();
        Log::channel($channel)->info("PRODUCT SHOW LEGACY | id={$id} | direct DB query, no cache");

        return Product::with('image')->findOrFail($id)->toArray();
    }

    private function optimizedShow($id)
    {
        $channel = $this->getLogChannel();

        if (!is_numeric($id) || (int) $id <= 0) { return ['error' => 'Invalid Product ID', 'status' => 422]; }
        $id = (int) $id;
        $cacheKey = $this->productDetailCacheKey($id);

        $data = Cache::get($cacheKey);
        if ($data) {
            Log::channel($channel)->info("PRODUCT CACHE HIT | id={$id}");
            return $data;
        }

        Log::channel($channel)->info("PRODUCT CACHE MISS | id={$id} | acquiring distributed lock");

        Cache::lock("lock:product:$id", 10)->block(5, function () use ($id, $cacheKey, $channel) {
            if (Cache::has($cacheKey)) {
                Log::channel($channel)->info("PRODUCT LOCK | id={$id} | cache populated by another process, skip DB");
                return;
            }
            Log::channel($channel)->info("PRODUCT LOCK ACQUIRED | id={$id} | querying DB");
            $product = Product::with('image')->find($id);
            $result  = $product ? $product->toArray() : null;
            Cache::put($cacheKey, $result, now()->addHours(24));
            Log::channel($channel)->info("PRODUCT CACHE STORED | id={$id}");
        });

        $data = Cache::get($cacheKey);

        if (!$data) {
            Log::channel($channel)->warning("PRODUCT NOT FOUND | id={$id}");
            return ['error' => 'Product not found', 'status' => 404];
        }

        return $data;
    }
}