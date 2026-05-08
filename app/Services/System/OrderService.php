<?php

namespace App\Services\System;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class OrderService
{
    # Add (Pagination - Caching (Redis) - Batch Processing)
    public function getUserOrders()
    {
        return Order::with('items.product')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    # we have (Race Condition) - we use ( Transaction + Pessimistic Locking )
    # Better use Optimistic Locking - Add (Async Queue - Batch Insert - Capacity Control) - Remove Double Lockind
    public function placeOrder()
    {
        $userId = auth()->id();

        return DB::transaction(function () use ($userId) {
            $cartItems = Cart::where('user_id', $userId)
                ->with('product')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                return $this->error('Cart is empty');
            }

            $preparedItems = [];
            $totalPrice    = 0;

            foreach ($cartItems as $cartItem) {
                $product = Product::lockForUpdate()->find($cartItem->product_id);

                if (!$product) {
                    return $this->error('Product not found', 404);
                }

                if ($product->quantity < $cartItem->quantity) {
                    return $this->error("Not enough stock for product: {$product->name}", 422);
                }

                $unitPrice = (float) $product->price;

                $preparedItems[] = [
                    'product'    => $product,
                    'product_id' => $product->id,
                    'quantity'   => $cartItem->quantity,
                    'price'      => $unitPrice,
                ];

                $totalPrice += $unitPrice * $cartItem->quantity;
            }

            $order = Order::create([
                'user_id'     => $userId,
                'total_price' => $totalPrice,
                'status'      => 'pending',
            ]);

            foreach ($preparedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);

                $item['product']->decrement('quantity', $item['quantity']);
            }

            Cart::where('user_id', $userId)->delete();

            return $order->load('items.product');
        });
    }

    # we have (data Integrity Problem) - we use (Transaction + Pessimistic Locking) 
    # we have Race COndition (because we check order status out of Transaction)
    # Add (Async Notification - Cache Invalidation)
    public function cancelOrder($id)
    {
        $order = Order::with('items')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        if ($order->status === 'cancelled') {
            return $this->error('Order is already cancelled');
        }

        if ($order->status !== 'pending') {
            return $this->error('This order cannot be cancelled');
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product) {
                    $product->increment('quantity', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);

            return $order->fresh()->load('items.product');
        });
    }

    # Add (Caching)
    public function show($orderId)
    {
        $order = Order::with('items.product')
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $order;
    }

    # we have ( Race Condition + Data Integrity ) - we use (Transaction + Pessimistic Locking)
    # we have Race COndition (because we check order status out of Transaction)
    # better use Optimistic Locking - Add (VAlidation for Quantity and Cache Invalidation)
    public function updateProductQuantity($orderId, $productId, $quantity)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        if ($order->status !== 'pending') {
            return $this->error('This order cannot be edited');
        }

        return DB::transaction(function () use ($order, $productId, $quantity) {
            $orderItem = $order->items()
                ->where('product_id', $productId)
                ->first();

            if (!$orderItem) {
                return $this->error('Product not found in this order', 404);
            }

            $product = Product::lockForUpdate()->find($productId);

            if (!$product) {
                return $this->error('Product not found', 404);
            }

            if ($quantity === 0) {
                $product->increment('quantity', $orderItem->quantity);
                $orderItem->delete();
                $this->recalculateOrderTotal($order);

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

            return $order->fresh()->load('items.product');
        });
    }

    # Extra DB query inside Transaction
    # Better: calculate total directly from $preparedItems in memory
    # to avoid additional SELECT while locks are held
    private function recalculateOrderTotal(Order $order): void
    {
        $totalPrice = $order->items()
            ->selectRaw('SUM(quantity * price) as total')
            ->value('total') ?? 0;

        $order->update(['total_price' => $totalPrice]);
    }

    private function error(string $message, int $status = 400): array
    {
        return [
            'error'  => $message,
            'status' => $status,
        ];
    }
}