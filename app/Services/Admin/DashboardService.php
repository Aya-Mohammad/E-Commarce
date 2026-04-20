<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;

class DashboardService
{
    public function getStats()
    {
        $totalUsers = User::count();
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalStores = Store::count();

        $pendingOrders = Order::where('status', 'pending')->count();
        $approvedOrders = Order::where('status', 'approved')->count();
        $rejectedOrders = Order::where('status', 'rejected')->count();
        $deliveredOrders = Order::where('status', 'delivered')->count();

        return response()->json([
            'users' => $totalUsers,
            'orders' => $totalOrders,
            'products' => $totalProducts,
            'stores' => $totalStores,

            'orders_status' => [
                'pending' => $pendingOrders,
                'approved' => $approvedOrders,
                'rejected' => $rejectedOrders,
                'delivered' => $deliveredOrders,
            ],
        ]);
    }
}