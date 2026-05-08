<?php

namespace App\Services\System;

use App\Models\Store;

class StoreService
{
    # Add (Caching (Redis) - store list is static data, perfect Cache candidate)
    # Add (Cache Invalidation - when store is added/updated/deleted)
    # Add (Filtering - no filter by distance, working hours, delivery cost)
    # Pagination already exists 
    public function index(int $perPage = 15)
    {
        return Store::with('image')->paginate($perPage);
    }

    # Add (Caching (Redis) - single store data rarely changes)
    # Add (Cache Invalidation - when store is updated)
    # Add (load 'products' relation - user needs to see store products)
    # Validation for ID already exists 
    public function show($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        return Store::with('image')->find((int) $id);
    }
}

# Missing Functions:
# - getStoreProducts($storeId) - no way to get products of a specific store
# - filterByDistance()         - distance field exists in DB but unused
# - filterByWorkingHours()     - start_of_work / end_of_work exist in DB but unused
# - filterByDeliveryCost()     - delivery_cost exists in DB but unused