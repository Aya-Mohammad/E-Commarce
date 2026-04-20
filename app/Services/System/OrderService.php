<?php

namespace App\Services\System;

use App\Models\Order;
use App\Models\Product;
use App\Models\DeteilsOfOrder;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function getUserOrders()
    {
        return Order::with('deteilsoforder')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function placeOrder(array $data)
    {
        $user = auth()->user();

        $cartItems = Cart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return ['error' => 'السلة فارغة'];
        }

        return DB::transaction(function () use ($data, $user) {

            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $data['total_price'],
                'status' => 'pending',
            ]);

            foreach ($data['items'] as $item) {

                $orderItem = $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                $product = Product::find($orderItem->product_id);

                if ($product && $product->quantity < $orderItem->quantity) {
                    $product->quantity -= $orderItem->quantity;
                    $product->save();
                }

                DeteilsOfOrder::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            Cart::where('user_id', $user->id)->delete();

            return $order;
        });
    }

    public function cancelOrder($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return ['error' => 'not found'];
        }

        if ($order->status === 'cancelled') {
            return ['error' => 'already cancelled'];
        }

        return DB::transaction(function () use ($order) {

            $details = DeteilsOfOrder::where('order_id', $order->id)->get();

            foreach ($details as $detail) {
                $product = Product::find($detail->product_id);

                if ($product) {
                    $product->quantity += $detail->quantity;
                    $product->save();
                }
            }

            $order->status = 'cancelled';
            $order->save();

            return $order;
        });
    }

    public function manageOrder($orderId)
    {
        return Order::with('deteilsoforder.product')
            ->find($orderId);
    }

    public function updateProductQuantity($orderId, $productId, $quantity)
    {
        $orderDetail = DeteilsOfOrder::where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$orderDetail) return null;

        $product = Product::find($productId);

        $diff = $quantity - $orderDetail->quantity;

        if ($diff > 0) {
            if ($product->quantity < $diff) {
                return ['error' => 'not enough stock'];
            }

            $product->quantity -= $diff;
        } else {
            $product->quantity += abs($diff);
        }

        $product->save();

        $orderDetail->update([
            'quantity' => $quantity
        ]);

        return $orderDetail;
    }

    public function deleteProductFromOrder($orderId, $productId)
    {
        $detail = DeteilsOfOrder::where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$detail) return null;

        $product = Product::find($productId);

        if ($product) {
            $product->quantity += $detail->quantity;
            $product->save();
        }

        $detail->delete();

        return true;
    }
}