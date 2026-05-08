<?php

namespace App\Services\System;

use App\Models\Product;

class ProductService
{
    # Add (Caching (Redis) - product list rarely changes, perfect Cache candidate)
    # Add (Cache Invalidation - when product is added/updated/deleted)
    # Add (Filtering & Sorting - no search/filter by price, name, store, etc.)
    # Pagination already exists 
    public function index(int $perPage = 15)
    {
        return Product::with('image')->paginate($perPage);
    }

    # Add (Caching (Redis) - single product data rarely changes)
    # Add (Cache Invalidation - when product is updated or deleted)
    # Validation for ID already exists 
    # Missing: load 'store' relation alongside 'image' - user may need store info
    public function show($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        return Product::with('image')->find((int) $id);
    }
}

# Missing Functions:
# - search(string $query) - no search functionality
# - getByStore($storeId)  - no filter by store
# - getFavourites()       - favourites logic not here (may be elsewhere)