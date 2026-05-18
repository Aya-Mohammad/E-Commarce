<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Services\System\OrderService;
use App\Http\Requests\System\Order\StoreOrderRequest;
use App\Http\Requests\System\Order\UpdateProductQuantityRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Redis;

class OrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private OrderService $service) {}

    public function index()
    {
        return $this->apiResponse(
            $this->service->getUserOrders(),
            'Orders fetched successfully',
            200
        );
    }

    public function store(StoreOrderRequest $request)
    {
        $currentOrders = Redis::incr('global_orders_count');

        if ($currentOrders == 1) {
            Redis::expire('global_orders_count', 60);
        }

        if ($currentOrders > 60) {
            return $this->apiResponse(
                null,
                'Too Many Requests. The server is currently overloaded, please try again later.',
                429
            );
        }
        return $this->respond(
            $this->service->placeOrder(),
            'Order placed successfully',
            201
        );
    }

    public function cancel($id)
    {
        return $this->respond(
            $this->service->cancelOrder($id),
            'Order cancelled successfully'
        );
    }

    public function show($id)
    {
        return $this->respond(
            $this->service->show($id),
            'Order fetched successfully'
        );
    }

    public function updateProductQuantity(UpdateProductQuantityRequest $request, $orderId)
    {
        return $this->respond(
            $this->service->updateProductQuantity(
                $orderId,
                $request->product_id,
                $request->quantity
            ),
            'Order updated successfully'
        );
    }

    private function respond($result, string $message, int $status = 200)
    {
        if (is_array($result) && array_key_exists('error', $result)) {
            return $this->apiResponse(
                null,
                $result['error'],
                $result['status'] ?? 400
            );
        }

        return $this->apiResponse($result, $message, $status);
    }
}