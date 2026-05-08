<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;

class DashboardService
{

# Add (Caching (Redis) - this function hits 4 tables in one call, very expensive)
# perfect Cache candidate, refresh every few minutes (TTL-based Cache)
# Add (Async Queue / Background Job - stats can be pre-calculated periodically)
# instead of calculating on every admin request
# Risk: 4 separate COUNT queries hidden inside (User::count, Product::count, Store::count)
# + 1 heavy selectRaw = 4 DB hits per request with no Cache
# Fix: combine all counts in one query or use Cached counters
# Missing: no date filtering - no way to get stats for specific period (today, this month)
# Missing: no revenue stats - total_price sum not included
# Missing: no top products / top stores stats
    public function getStats(): array
    {
        $orderStats = Order::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled
        ', ['pending', 'approved', 'rejected', 'delivered', 'cancelled'])
        ->first();

        return [
            'users'    => User::count(),
            'orders'   => $orderStats->total,
            'products' => Product::count(),
            'stores'   => Store::count(),

            'orders_status' => [
                'pending'   => $orderStats->pending,
                'approved'  => $orderStats->approved,
                'rejected'  => $orderStats->rejected,
                'delivered' => $orderStats->delivered,
                'cancelled' => $orderStats->cancelled,
            ],
        ];
    }
}