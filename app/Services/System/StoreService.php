<?php

namespace App\Services\System;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class StoreService
{
    // Caching (Redis) - store list is static data, perfect Cache candidate
    // Cache Invalidation - when store is added/updated/deleted
    // Filtering - no filter by distance, working hours, delivery cost
    // Pagination already exists
    public function index(int $perPage = 15)
    {
        // الحصول على رقم الصفحة الحالية ليكون جزءاً من مفتاح الكاش
        $page = request()->get('page', 1);

        // إنشاء مفتاح فريد لكل صفحة ولكل عدد عناصر (مثلاً: stores_page_1_limit_15)
        $cacheKey = "stores_page_{$page}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($perPage) {
            // جلب البيانات مع الصور بنظام الترقيم
            return Store::with('image')->paginate($perPage);
        });
    }

    // Filter
    public function filter()
    {
        // filter by distance, working hours, delivery cost
        $distance = request()->query('distance');
        $startWorking = request()->query('start_working');
        $endWorking = request()->query('end_working');
        $deliveryCost = request()->query('delivery_cost');

        $workingHours = $endWorking - $startWorking;
        $query = Store::query();
        if ($distance) {
            $query->where('distance', '<=', $distance);
        }
        if ($workingHours) {
            $query->whereRaw('(end_of_work - start_of_work) >= ?', [$workingHours]);
        }
        if ($deliveryCost) {
            $query->where('delivery_cost', '<=', $deliveryCost);
        }

        return $query->get();
    }

    // Caching (Redis) - single store data rarely changes
    // Cache Invalidation - when store is updated
    // load 'products' relation - user needs to see store products
    // Validation for ID already exists
    public function show($id)
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }
        // ========= Before Caching =========
        // return Store::with('image')->find((int) $id);
        // ==================================

        // ========= After Caching =========
        if (! is_numeric($id) || (int) $id <= 0) {
            return response()->json(['message' => 'Invalid ID'], 400);
        }

        $cacheKey = 'store_details_'.$id;
        $store = Cache::remember($cacheKey, now()->addDays(7), function () use ($id) {
            return Store::with(['image', 'products'])->findOrFail((int) $id);
        });

        return response()->json($store);
    }

    // get products of a specific store
    public function getStoreProducts($storeId, int $perPage = 15)
    {
        if (! is_numeric($storeId) || (int) $storeId <= 0) {
            return null;
        }

        return Store::find((int) $storeId)->products()->paginate($perPage);
    }
}

// Missing Functions:
// Added - getStoreProducts($storeId) - no way to get products of a specific store
// Added - filterByDistance()         - distance field exists in DB but unused
// Added - filterByWorkingHours()     - start_of_work / end_of_work exist in DB but unused
// Added - filterByDeliveryCost()     - delivery_cost exists in DB but unused
