<?php

namespace App\Services\System;

use App\Models\Product;

class ProductService
{
    // Caching (Redis) - product list rarely changes, perfect Cache candidate
    // Cache Invalidation - when product is added/updated/deleted
    // Add (Filtering & Sorting - no search/filter by price, name, store, etc.)
    // Pagination already exists
    public function index(int $perPage = 15)
    {
        // return Product::with('image')->paginate($perPage);
        // الحصول على رقم الصفحة الحالية ليكون جزءاً من مفتاح الكاش
        $page = request()->get('page', 1);

        // إنشاء مفتاح فريد لكل صفحة ولكل عدد عناصر (مثلاً: products_page_1_limit_15)
        $cacheKey = "products_page_{$page}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($perPage) {
            // جلب البيانات مع الصور بنظام الترقيم
            return Product::with('image')->paginate($perPage);
        });
    }

    // Caching (Redis) - single product data rarely changes
    // Cache Invalidation - when product is updated or deleted
    // Validation for ID already exists
    // load 'store' relation alongside 'image' - user may need store info
    public function show($id)
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }
        // ========= Before Caching =========
        // return Product::with('image')->find((int) $id);
        // ==================================

        // ========= After Caching =========
        if (! is_numeric($id) || (int) $id <= 0) {
            return response()->json(['message' => 'Invalid ID'], 400);
        }

        $cacheKey = 'product_details_'.$id;
        $product = Cache::remember($cacheKey, now()->addDays(7), function () use ($id) {
            return Product::with('image')->findOrFail((int) $id);
        });

        return response()->json($product);
    }
}

// Missing Functions:
// - search(string $query) - no search functionality
// - getByStore($storeId)  - no filter by store
// - getFavourites()       - favourites logic not here (may be elsewhere)
