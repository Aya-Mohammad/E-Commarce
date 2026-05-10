<?php

namespace App\Services\System;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderService
{
    # Pagination + Distributed Caching (6)
    public function getUserOrders()
    {
        $page = request('page', 1);

        return Cache::remember(
            "user_orders_" . auth()->id() . "_page_" . $page,
            60,
            function () {

                return Order::with('items.product')
                    ->where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
            }
        );
    }

    # Add Async Queue ________________________________________
   public function placeOrder()
    {
        $userId = auth()->id();

        /**
         * =========================
         * Capacity Control
         * =========================
         */
        $maxCartItems = 50;

        // $cartItemsCount = Cache::remember("cart_count:{$userId}", 300, function () use ($userId) {
        //     return Cart::where('user_id', $userId)->count();
        // });

        $cartItemsCount = Cart::where('user_id', $userId)->count();

        if ($cartItemsCount > $maxCartItems) {
            return $this->error(
                "Cart cannot exceed {$maxCartItems} different products",
                422
            );
        }

        /**
         * =========================
         * Distributed Lock (Redis)
         * =========================
         */
        // $lock = Cache::lock("place_order:{$userId}", 10);

        if (!$lock->get()) {
            return $this->error('Another order is being processed', 429);
        }

        try {

            return DB::transaction(function () use ($userId) {

                /**
                 * =========================
                 * Cart (cached read)
                 * =========================
                 */
                // $cartItems = Cache::remember("cart:{$userId}", 120, function () use ($userId) {
                //     return Cart::where('user_id', $userId)
                //         ->with('product')
                //         ->get();
                // });

                $cartItems = Cart::where('user_id', $userId)
                ->with('product')
                ->get();

                if ($cartItems->isEmpty()) {
                    return $this->error('Cart is empty', 422);
                }

                $productIds = $cartItems->pluck('product_id')->unique()->values();

                /**
                 * =========================
                 * Lock products (critical section)
                 * =========================
                 */
                $products = Product::whereIn('id', $productIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $preparedItems = [];
                $totalPrice = 0;

                foreach ($cartItems as $cartItem) {

                    $product = $products->get($cartItem->product_id);

                    if (!$product) {
                        return $this->error('Product not found', 404);
                    }

                    if ($product->quantity < $cartItem->quantity) {
                        return $this->error(
                            "Not enough stock for product: {$product->name}",
                            422
                        );
                    }

                    $price = (float) $product->price;

                    $preparedItems[] = [
                        'product'    => $product,
                        'product_id' => $product->id,
                        'quantity'   => $cartItem->quantity,
                        'price'      => $price,
                    ];

                    $totalPrice += $price * $cartItem->quantity;
                }

                /**
                 * =========================
                 * Create Order
                 * =========================
                 */
                $order = Order::create([
                    'user_id'     => $userId,
                    'total_price' => $totalPrice,
                    'status'      => 'pending',
                ]);

                /**
                 * =========================
                 * Batch insert + stock update
                 * =========================
                 */
                $orderItems = [];

                foreach ($preparedItems as $item) {

                    $orderItems[] = [
                        'order_id'   => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $item['product']->decrement('quantity', $item['quantity']);
                }

                OrderItem::insert($orderItems);

                /**
                 * =========================
                 * Clear cart
                 * =========================
                 */
                Cart::where('user_id', $userId)->delete();

                /**
                 * =========================
                 * Clear only relevant cache (NO FLUSH)
                 * =========================
                 */
                // Cache::forget("cart:{$userId}");
                // Cache::forget("cart_count:{$userId}");
                // Cache::forget("orders:{$userId}");
                // Cache::forget("user_orders:{$userId}");

                return $order->load('items.product');
            });

        } finally {
            $lock->release();
        }
    }
    # Add Async Notification_______________________________________________
    public function cancelOrder($id)
    {
        return DB::transaction(function () use ($id) {

        # Pessimistic Locking (1) - Handle Race Conditions
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->lockForUpdate()
            ->with('items')
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        if ($order->status !== 'pending') {
            return $this->error('This order cannot be cancelled');
        }

        foreach ($order->items as $item) {
            $product = Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $product->increment('quantity', $item->quantity);

                # Cache Invalidation (part of 6)
                Cache::forget("product:{$product->id}");
            }
        }

        $order->update(['status' => 'cancelled']);

        # Cache Invalidation (part of 6)
        Cache::forget("order:{$order->id}");

        return $order->fresh()->load('items.product');
    });
    }

    public function show($orderId)
    {
        $cacheKey = 'order_' . auth()->id() . '_' . $orderId;

        # Distributed Caching (6)
        $order = Cache::remember($cacheKey, 300, function () use ($orderId) {
            return Order::with('items.product')
                ->where('id', $orderId)
                ->where('user_id', auth()->id())
                ->first();
        });

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $order;
    }

    public function updateProductQuantity($orderId, $productId, $quantity)
    {
        # Capacity Control (2)
        if (!is_numeric($quantity) || $quantity < 0) {
            return $this->error('Invalid quantity value', 422);
        }

        return DB::transaction(function () use ($orderId, $productId, $quantity) {

            # Pessimistic Locking (1) - Handle Race Conditions
            $order = Order::where('id', $orderId)
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return $this->error('Order not found', 404);
            }

            if ($order->status !== 'pending') {
                return $this->error('This order cannot be edited');
            }

            $orderItem = $order->items()
                ->where('product_id', $productId)
                ->first();

            if (!$orderItem) {
                return $this->error('Product not found in this order', 404);
            }

            # Pessimistic Locking (1) - Handle Race Conditions
            $product = Product::lockForUpdate()->find($productId);

            if (!$product) {
                return $this->error('Product not found', 404);
            }

            if ($quantity === 0) {

                $product->increment('quantity', $orderItem->quantity);
                $orderItem->delete();

                $this->recalculateOrderTotal($order);

                # Cache Invalidation (part of 6)
                Cache::forget("product_{$productId}");
                Cache::forget("order_{$orderId}");  

                return $order->fresh()->load('items.product');
            }

            $diff = $quantity - $orderItem->quantity;

            if ($diff > 0) {
                if ($product->quantity < $diff) {
                    return $this->error('Not enough stock', 422);
                }
                $product->decrement('quantity', $diff);
            } elseif ($diff < 0) {
                $product->increment('quantity', abs($diff));
            }

            $orderItem->update(['quantity' => $quantity]);

            $this->recalculateOrderTotal($order);

            # cache Invalidation (part of 6)
            Cache::forget("product_{$productId}");
            Cache::forget("order_{$orderId}");

            return $order->fresh()->load('items.product');
        });
    }

    private function recalculateOrderTotalFromItems(Order $order, $items): void
    {
        $totalPrice = 0;

        foreach ($items as $item) {
            $totalPrice += $item->quantity * $item->price;
        }

        $order->update([
            'total_price' => $totalPrice
        ]);
    }

    private function error(string $message, int $status = 400): array
    {
        return [
            'error'  => $message,
            'status' => $status,
        ];
    }
}