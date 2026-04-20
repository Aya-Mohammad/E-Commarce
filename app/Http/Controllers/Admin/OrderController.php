<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\OrderService;
use App\Http\Requests\Admin\HandleOrderRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function handleOrder(HandleOrderRequest $request, $orderId)
    {
        return $this->orderService->handleOrder($orderId, $request->action);
    }

    public function getAllOrders(Request $request)
    {
        return $this->orderService->getAllOrders($request->status);
    }

    public function getPendingOrders()
    {
        return $this->orderService->getPendingOrders();
    }

    public function updateStatus(UpdateOrderStatusRequest $request, $orderId)
    {
        return $this->orderService->updateStatus($orderId, $request->status);
    }
}