<?php

namespace App\Services\System;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function getUserOrders()
    {
        return Order::with('items.product')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function placeOrder()
    {
        $userId = auth()->id();

        return DB::transaction(function () use ($userId) {
            $cartItems = Cart::where('user_id', $userId)
                ->with('product')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                return $this->error('السلة فارغة');
            }

            $preparedItems = [];
            $totalPrice = 0;

            foreach ($cartItems as $cartItem) {
                $product = Product::lockForUpdate()->find($cartItem->product_id);

                if (!$product) {
                    return $this->error('المنتج غير موجود', 404);
                }

                if ($product->quantity < $cartItem->quantity) {
                    return $this->error("الكمية غير متوفرة للمنتج {$product->name}", 422);
                }

                $unitPrice = (float) $product->price;

                $preparedItems[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'quantity' => $cartItem->quantity,
                    'price' => $unitPrice,
                ];

                $totalPrice += $unitPrice * $cartItem->quantity;
            }

            $order = Order::create([
                'user_id' => $userId,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            foreach ($preparedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                $item['product']->quantity -= $item['quantity'];
                $item['product']->save();
            }

            Cart::where('user_id', $userId)->delete();

            return $order->load('items.product');
        });
    }

    public function cancelOrder($id)
    {
        $order = Order::with('items')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('not found', 404);
        }

        if ($order->status === 'cancelled') {
            return $this->error('already cancelled');
        }

        if ($order->status !== 'pending') {
            return $this->error('can not cancel this order');
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product) {
                    $product->quantity += $item->quantity;
                    $product->save();
                }
            }

            $order->update(['status' => 'cancelled']);

            return $order->fresh()->load('items.product');
        });
    }

    public function show($orderId)
    {
        $order = Order::with('items.product')
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('not found', 404);
        }

        return $order;
    }

    public function updateProductQuantity($orderId, $productId, $quantity)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('not found', 404);
        }

        if ($order->status !== 'pending') {
            return $this->error('can not edit this order');
        }

        return DB::transaction(function () use ($order, $productId, $quantity) {
            $orderItem = $order->items()
                ->where('product_id', $productId)
                ->first();

            if (!$orderItem) {
                return $this->error('not found', 404);
            }

            $product = Product::lockForUpdate()->find($productId);

            if (!$product) {
                return $this->error('product not found', 404);
            }

            $diff = $quantity - $orderItem->quantity;

            if ($diff > 0) {
                if ($product->quantity < $diff) {
                    return $this->error('not enough stock', 422);
                }

                $product->quantity -= $diff;
            } elseif ($diff < 0) {
                $product->quantity += abs($diff);
            }

            $product->save();

            $orderItem->update([
                'quantity' => $quantity,
            ]);

            $this->recalculateOrderTotal($order);

            return $order->fresh()->load('items.product');
        });
    }

    private function recalculateOrderTotal(Order $order): void
    {
        $totalPrice = $order->items()
            ->get()
            ->sum(fn (OrderItem $item) => $item->quantity * (float) $item->price);

        $order->update([
            'total_price' => $totalPrice,
        ]);
    }

    private function error(string $message, int $status = 400): array
    {
        return [
            'error' => $message,
            'status' => $status,
        ];
    }
}
