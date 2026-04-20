<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Services\System\OrderService;

use App\Http\Requests\System\Order\StoreOrderRequest;
use App\Http\Requests\System\Order\UpdateOrderRequest;
use App\Http\Requests\System\Order\UpdateProductQuantityRequest;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private OrderService $service) {}

    /*
    |----------------------------------------
    | GET USER ORDERS
    |----------------------------------------
    */
    public function index()
    {
        return $this->apiResponse(
            $this->service->getUserOrders(),
            'OK',
            200
        );
    }

    /*
    |----------------------------------------
    | PLACE ORDER
    |----------------------------------------
    */
    public function store(StoreOrderRequest $request)
    {
        return $this->apiResponse(
            $this->service->placeOrder($request->validated()),
            'Order placed',
            201
        );
    }

    /*
    |----------------------------------------
    | CANCEL ORDER
    |----------------------------------------
    */
    public function cancel($id)
    {
        return $this->apiResponse(
            $this->service->cancelOrder($id),
            'Cancelled'
        );
    }

    /*
    |----------------------------------------
    | MANAGE ORDER
    |----------------------------------------
    */
    public function manage($id)
    {
        return $this->apiResponse(
            $this->service->manageOrder($id),
            'OK'
        );
    }

    /*
    |----------------------------------------
    | UPDATE PRODUCT QUANTITY
    |----------------------------------------
    */
    public function updateProductQuantity(UpdateProductQuantityRequest $request, $orderId, $productId)
    {
        return $this->apiResponse(
            $this->service->updateProductQuantity(
                $orderId,
                $productId,
                $request->quantity
            ),
            'Updated'
        );
    }

    /*
    |----------------------------------------
    | DELETE PRODUCT FROM ORDER
    |----------------------------------------
    */
    public function deleteProduct($orderId, $productId)
    {
        return $this->apiResponse(
            $this->service->deleteProductFromOrder($orderId, $productId),
            'Deleted'
        );
    }
}