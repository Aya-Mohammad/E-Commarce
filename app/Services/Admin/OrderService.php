<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;

class OrderService
{
    use ApiResponseTrait;

    public function handleOrder($orderId, $action)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return $this->apiResponse(null, 'Order not found', 404);
        }

        if ($order->status !== 'pending') {
            return $this->apiResponse(null, 'Can\'t edit this order', 400);
        }

        if ($action === 'approve') {
            return $this->approveOrder($order);
        }

        if ($action === 'reject') {
            return $this->rejectOrder($order);
        }

        return $this->apiResponse(null, 'Wrong action', 400);
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

            return $this->apiResponse([
                'order' => $order
            ], 'Order approved');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->apiResponse(null, 'Failed to process order', 500, ['exception' => [$e->getMessage()]]);
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

            return $this->apiResponse([
                'order' => $order
            ], 'Order rejected');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->apiResponse(null, 'Error while rejecting order', 500, ['exception' => [$e->getMessage()]]);
        }
    }

    public function getAllOrders($status = null)
    {
        $query = Order::with('items', 'user');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return $this->apiResponse(['orders' => $orders], 'Orders fetched successfully');
    }

    public function getPendingOrders()
    {
        $orders = Order::with('items', 'user')
            ->where('status', 'pending')
            ->get();

        return $this->apiResponse(['orders' => $orders], 'Pending orders fetched successfully');
    }

    public function updateStatus($orderId, $status)
    {
        $order = Order::findOrFail($orderId);

        $order->update(['status' => $status]);

        return $this->apiResponse([
            'order' => $order
        ], 'Status updated');
    }
}