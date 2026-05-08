<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    # we have (Race Condition) - status check happens OUTSIDE Transaction
    # Two admin requests can pass the status check simultaneously
    # Fix: move order fetch + status check INSIDE Transaction with lockForUpdate()
    public function handleOrder(int $orderId, string $action): array
    {
        $order = Order::find($orderId);

        if (!$order) {
            return ['error' => 'Order not found', 'status' => 404];
        }

        if ($order->status !== 'pending') {
            return ['error' => 'Cannot edit this order', 'status' => 400];
        }

        if ($action === 'approve') {
            return $this->approveOrder($order);
        }

        return $this->rejectOrder($order);
    }

    # Add (Async Notification - notify user when order is approved via Queue)
    # Add (Cache Invalidation - invalidate order cache after status change)
    # Risk: no lockForUpdate() on order before update - Race Condition with rejectOrder()
    private function approveOrder(Order $order): array
    {
        DB::beginTransaction();

        try {
            $order->update(['status' => 'approved']);

            DB::commit();

            return ['data' => $order->load('items.product', 'user')];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving order: ' . $e->getMessage());
            throw $e;
        }
    }

    # we have (Race Condition) - uses Pessimistic Locking on Product 
    # but NO lockForUpdate() on Order itself before status update
    # Add (Async Notification - notify user when order is rejected via Queue)
    # Add (Cache Invalidation - invalidate order cache after status change)
    # Add (Batch Processing - product quantity restore done one by one in foreach)
    # Better: use single batch update instead of loop
    private function rejectOrder(Order $order): array
    {
        DB::beginTransaction();

        try {
            $orderItems = OrderItem::where('order_id', $order->id)->get();

            foreach ($orderItems as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product) {
                    $product->increment('quantity', $item->quantity);
                }
            }

            $order->update(['status' => 'rejected']);

            DB::commit();

            return ['data' => $order->load('items.product', 'user')];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting order: ' . $e->getMessage());
            throw $e;
        }
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