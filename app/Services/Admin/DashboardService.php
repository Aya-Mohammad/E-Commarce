<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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
    // public function getStats(): array
    // {
    //     $orderStats = Order::selectRaw('
    //         COUNT(*) as total,
    //         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
    //         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
    //         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected,
    //         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered,
    //         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled
    //     ', ['pending', 'approved', 'rejected', 'delivered', 'cancelled'])
    //     ->first();

    //     return [
    //         'users'    => User::count(),
    //         'orders'   => $orderStats->total,
    //         'products' => Product::count(),
    //         'stores'   => Store::count(),

    //         'orders_status' => [
    //             'pending'   => $orderStats->pending,
    //             'approved'  => $orderStats->approved,
    //             'rejected'  => $orderStats->rejected,
    //             'delivered' => $orderStats->delivered,
    //             'cancelled' => $orderStats->cancelled,
    //         ],
    //     ];
    // }


    // after first edit 
//     public function getStats(): array
// {
  
//     return Cache::remember('admin_dashboard_stats', now()->addMinutes(10), function () {
       
//         $orderStats = Order::selectRaw('
//             COUNT(*) as total,
//             SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
//             SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
//             SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
//             SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
//             SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
//         ')->first();

//         return [
//             'users'    => User::count(),
//             'orders'   => $orderStats->total,
//             'products' => Product::count(),
//             'stores'   => Store::count(),
//             'orders_status' => [
//                 'pending'   => $orderStats->pending,
//                 'approved'  => $orderStats->approved,
//                 'rejected'  => $orderStats->rejected,
//                 'delivered' => $orderStats->delivered,
//                 'cancelled' => $orderStats->cancelled,
//             ],
//         ];
//     });
// }



public function getStats(): array
{
    // استخدام Cache::remember لتقليل الضغط على السيرفر
    return Cache::remember('admin_dashboard_stats', now()->addMinutes(10), function () {
        
        /**
         * دمج جميع الاستعلامات في استعلام واحد (Single DB Hit)
         * نستخدم Subqueries لجلب العدادات من جداول Users, Products, Stores
         * ونستخدم الجدول الرئيسي Orders لحساب إحصائيات الطلبات والأرباح
         */
        $stats = DB::table('orders')
            ->selectRaw("
                (SELECT COUNT(*) FROM users) as users_count,
                (SELECT COUNT(*) FROM products) as products_count,
                (SELECT COUNT(*) FROM stores) as stores_count,
                COUNT(*) as total_orders,
                SUM(total_price) as total_revenue,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        // تنسيق البيانات المرجعة لتكون منظمة وسهلة الاستخدام في الواجهة الأمامية
        return [
            'overview' => [
                'users'    => (int) $stats->users_count,
                'products' => (int) $stats->products_count,
                'stores'   => (int) $stats->stores_count,
                'revenue'  => (float) ($stats->total_revenue ?? 0),
            ],
            'orders_metrics' => [
                'total'     => (int) $stats->total_orders,
                'pending'   => (int) $stats->pending,
                'approved'  => (int) $stats->approved,
                'rejected'  => (int) $stats->rejected,
                'delivered' => (int) $stats->delivered,
                'cancelled' => (int) $stats->cancelled,
            ],
            'last_updated' => now()->toDateTimeString(), // مفيد لمعرفة توقت تحديث الكاش
        ];
    });
}
}