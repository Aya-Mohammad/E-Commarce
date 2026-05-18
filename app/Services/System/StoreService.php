<?php
 
namespace App\Services\System;
 
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
 
class StoreService
{
    /**
     * Cache Key Strategy (6)
     */
    private function storeListCacheKey(int $page): string
    {
        return "stores:page:{$page}";
    }
 
    private function storeDetailCacheKey(int $id): string
    {
        return "store:detail:{$id}";
    }
 
    private function storeProductsCacheKey(int $storeId, int $page): string
    {
        return "store:{$storeId}:products:page:{$page}";
    }
 
    public function index(int $perPage = 15)
    {
        $page = (int) request()->get('page', 1);
 
        /**
         * Distributed Caching (6)
         * Resource Management (2)
         */
        return Cache::remember($this->storeListCacheKey($page), now()->addHour(), function () use ($perPage) {
            return Store::with('image')->paginate($perPage);
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
        return Cache::remember($this->storeDetailCacheKey($id), now()->addHours(24), function () use ($id) {
            return Store::with(['image', 'products'])->findOrFail($id);
        });
    }
 
    public function getStoreProducts($storeId, int $perPage = 15)
    {
        // Capacity Control (2)
        if (!is_numeric($storeId) || (int) $storeId <= 0) {
            return null;
        }
 
        $storeId = (int) $storeId;
        $page    = (int) request()->get('page', 1);
 
        /**
         * Distributed Caching (6)
         */
        $store = Store::find($storeId);
 
        if (!$store) {
            return null;
        }
 
        return Cache::remember($this->storeProductsCacheKey($storeId, $page), now()->addMinutes(30), function () use ($store, $perPage) {
            return $store->products()->with('image')->paginate($perPage);
        });
    }
 
    public function filter()
    {
        $distance     = request()->query('distance');
        $startWorking = request()->query('start_working');
        $endWorking   = request()->query('end_working');
        $deliveryCost = request()->query('delivery_cost');
 
        /**
         * Capacity Control (2)
         */
        if ($distance !== null && (!is_numeric($distance) || (float) $distance < 0)) {
            return ['error' => 'Invalid distance value', 'status' => 422];
        }
 
        if ($deliveryCost !== null && (!is_numeric($deliveryCost) || (float) $deliveryCost < 0)) {
            return ['error' => 'Invalid delivery cost value', 'status' => 422];
        }
 
        /**
         * Data Integrity (1)
         */
        $workingHours = null;
        if ($startWorking !== null && $endWorking !== null) {
            if (!is_numeric($startWorking) || !is_numeric($endWorking)) {
                return ['error' => 'Invalid working hours values', 'status' => 422];
            }
            if ((float) $endWorking <= (float) $startWorking) {
                return ['error' => 'end_working must be greater than start_working', 'status' => 422];
            }
            $workingHours = (float) $endWorking - (float) $startWorking;
        }
 
        $query = Store::query()->with('image');
 
        if ($distance !== null) {
            $query->where('distance', '<=', (float) $distance);
        }
 
        if ($workingHours !== null) {
            $query->whereRaw('(end_of_work - start_of_work) >= ?', [$workingHours]);
        }
 
        if ($deliveryCost !== null) {
            $query->where('delivery_cost', '<=', (float) $deliveryCost);
        }
 
        /**
         * Resource Management (2)
         */
        return $query->paginate(15);
    }
 
    /**
     * Cache Invalidation (6)
     */
    public function invalidateStoreCache(int $storeId): void
    {
        Cache::forget($this->storeDetailCacheKey($storeId));
         for ($page = 1; $page <= 10; $page++) {
            Cache::forget($this->storeListCacheKey($page));
            Cache::forget($this->storeProductsCacheKey($storeId, $page));
        }
    }
}