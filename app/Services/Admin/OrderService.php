<?php
 
namespace App\Services\Admin;
 
use App\Jobs\SendOrderApprovedNotificationJob;
use App\Jobs\SendOrderRejectedNotificationJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
 
class OrderService
{
    private function orderDetailCacheKey(int $orderId): string
    {
        return "admin:order:detail:{$orderId}";
    }
 
    private function orderListCacheKey(string $status, int $page): string
    {
        return "admin:orders:status:{$status}:page:{$page}";
    }
 
    public function handleOrder(int $orderId, string $action): array
    {
        /**
         * ACID Transaction (8) + Pessimistic Locking (7)
         */
        $result = DB::transaction(function () use ($orderId, $action) {
 
            // Pessimistic Locking (7) 
            $order = Order::where('id', $orderId)
                ->lockForUpdate()
                ->first();
 
            if (!$order) {
                return ['error' => 'Order not found', 'status' => 404];
            }
 
            // Race Condition Check (1)
            if ($order->status !== 'pending') {
                return ['error' => 'This order has already been processed', 'status' => 400];
            }
 
            $result = ($action === 'approve')
                ? $this->approveOrder($order)
                : $this->rejectOrder($order);
 
            /**
             * Cache Invalidation (6)
             */
            Cache::forget($this->orderDetailCacheKey($orderId));
            Cache::forget('admin_dashboard_stats');
            $this->invalidateOrderListCache();
 
            return $result;
        });
 
        /**
         * Async Queue (3)
         */
        if (!isset($result['error'])) {
            $order = $result['data'];
            if ($action === 'approve') {
                SendOrderApprovedNotificationJob::dispatch($order);
            } else {
                SendOrderRejectedNotificationJob::dispatch($order);
            }
        }
 
        return $result;
    }
 
    private function approveOrder(Order $order): array
    {
        $order->update(['status' => 'approved']);
 
        return ['data' => $order->load('items.product', 'user')];
    }
 
    private function rejectOrder(Order $order): array
    {
        $orderItems = OrderItem::where('order_id', $order->id)->get();
 
        $productIds = $orderItems->pluck('product_id')->sort()->values();
 
        /**
         * Pessimistic Locking (7) — Batch Lock
         * Batch Processing (4)
         */
        $products = Product::whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
 
        foreach ($orderItems as $item) {
            $product = $products->get($item->product_id);
            if ($product) {
                $product->increment('quantity', $item->quantity);
 
                // Cache Invalidation (6)
                Cache::forget("product:detail:{$item->product_id}");
            }
        }
 
        $order->update(['status' => 'rejected']);
 
        return ['data' => $order->load('items.product', 'user')];
    }

    private function invalidateOrderListCache(): void
    {
        $statuses = ['all', 'pending', 'approved', 'rejected', 'delivered', 'cancelled'];
 
        foreach ($statuses as $status) {
            for ($page = 1; $page <= 10; $page++) {
                Cache::forget($this->orderListCacheKey($status, $page));
            }
        }
    }
}