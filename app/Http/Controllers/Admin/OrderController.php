<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\OrderService;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function handleOrder($orderId)
    {
        return $this->orderService->handleOrder($orderId);
    }

    public function getAllOrders(Request $request)
    {
        return $this->orderService->getAllOrders($request->status);
    }

}
