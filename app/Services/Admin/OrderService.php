<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    # Two admin requests can pass the status check simultaneously
    # Fix: move order fetch + status check INSIDE Transaction with lockForUpdate()
    // public function handleOrder(int $orderId, string $action): array
    // {
    //     return DB::transaction(function () use ($orderId, $action) {

    //         $order = Order::lockForUpdate()->find($orderId); // ← داخل Transaction مع Lock

    //         if (!$order) {
    //             return ['error' => 'Order not found', 'status' => 404];
    //         }

    //         if ($order->status !== 'pending') {   // ← داخل Transaction
    //             return ['error' => 'Cannot edit this order', 'status' => 400];
    //         }

    //         if ($action === 'approve') {
    //             return $this->approveOrder($order);
    //         }

    //         return $this->rejectOrder($order);
    //     });
    // }



    public function handleOrder(int $orderId, string $action): array
{
    return DB::transaction(function () use ($orderId, $action) {
        $order = Order::where('id', $orderId)->lockForUpdate()->first();

        if (!$order) {
            return ['error' => 'الطلب غير موجود', 'status' => 404];
        }

        if ($order->status !== 'pending') {
            return ['error' => 'تمت معالجة هذا الطلب مسبقاً', 'status' => 400];
        }

        $result = ($action === 'approve') ? $this->approveOrder($order) : $this->rejectOrder($order);

        Cache::forget('admin_dashboard_stats');

        return $result;
    });
}

    # Add (Async Notification - notify user when order is approved via Queue)
    # Add (Cache Invalidation - invalidate order cache after status change)
    private function approveOrder(Order $order): array
    {
        $order->update(['status' => 'approved']);

        return ['data' => $order->load('items.product', 'user')];
    }

    # Add (Async Notification - notify user when order is rejected via Queue)
    # Add (Cache Invalidation - invalidate order cache after status change)
    # Add (Batch Processing - product quantity restore done one by one in foreach)
    # Better: use single batch update instead of loop
    // private function rejectOrder(Order $order): array
    // {
    //     $orderItems = OrderItem::where('order_id', $order->id)->get();

    //     $productIds = $orderItems->pluck('product_id')->sort()->values();

    //     $products = Product::whereIn('id', $productIds)
    //         ->orderBy('id')
    //         ->lockForUpdate()
    //         ->get()
    //         ->keyBy('id');

    //     foreach ($orderItems as $item) {
    //         $product = $products->get($item->product_id);

    //         if ($product) {
    //             $product->increment('quantity', $item->quantity);
    //         }
    //     }

    //     $order->update(['status' => 'rejected']);

    //     return ['data' => $order->load('items.product', 'user')];
    // }

    private function rejectOrder(Order $order): array
{
    $orderItems = OrderItem::where('order_id', $order->id)->get();

    foreach ($orderItems as $item) {
      
        Product::where('id', $item->product_id)->lockForUpdate()->increment('quantity', $item->quantity);
    }

    $order->update(['status' => 'rejected']);

    return ['data' => $order->load('items.product', 'user')];
}

    # Add (Caching (Redis) - filtered order lists can be cached per status)
    # Add (Cache Invalidation - when any order status changes)
    # Pagination already exists 
    # Filtering by status already exists 
    public function getAllOrders(?string $status = null, int $perPage = 15)
    {
        $allowed = ['pending', 'approved', 'rejected', 'delivered', 'cancelled'];

        $query = Order::with('items.product', 'user');

        if ($status && in_array($status, $allowed)) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    # Add (Caching (Redis) - single order data, good Cache candidate)
    # Add (Cache Invalidation - when order is updated/cancelled/approved/rejected)
    # Missing: no ownership check - any admin can see any order (this is fine for admin)
    public function getOrderById(int $id): ?Order
    {
        return Order::with('items.product', 'user')->find($id);
    }
}