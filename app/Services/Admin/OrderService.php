<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function handleOrder($orderId, $action)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Can\'t edit this order'], 400);
        }

        if ($action === 'approve') {
            return $this->approveOrder($order);
        }

        if ($action === 'reject') {
            return $this->rejectOrder($order);
        }

        return response()->json(['message' => 'Wrong action'], 400);
    }

    private function approveOrder($order)
    {
        DB::beginTransaction();

        try {
            $orderItems = OrderItem::where('order_id', $order->id)->get();

            foreach ($orderItems as $item) {
                $product = Product::find($item->product_id);

                if ($product) {
                    $product->quantity -= $item->quantity;
                    $product->save();
                }
            }

            $order->status = 'approved';
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order approved',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to process order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function rejectOrder($order)
    {
        DB::beginTransaction();

        try {
            $orderItems = OrderItem::where('order_id', $order->id)->get();

            foreach ($orderItems as $item) {
                $product = Product::find($item->product_id);

                if ($product) {
                    $product->quantity += $item->quantity;
                    $product->save();
                }
            }

            $order->status = 'rejected';
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order rejected',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error while rejecting order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllOrders($status = null)
    {
        $query = Order::with('items', 'user');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['orders' => $orders]);
    }

    public function getPendingOrders()
    {
        $orders = Order::with('items', 'user')
            ->where('status', 'pending')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function updateStatus($orderId, $status)
    {
        $order = Order::findOrFail($orderId);

        $order->update(['status' => $status]);

        return response()->json([
            'message' => 'Status updated',
            'order' => $order
        ]);
    }
}