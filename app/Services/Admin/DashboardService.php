<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Traits\ApiResponseTrait;

class DashboardService
{
    use ApiResponseTrait;

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

        return $this->apiResponse([
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
        ], 'Dashboard stats fetched successfully');
    }
}
