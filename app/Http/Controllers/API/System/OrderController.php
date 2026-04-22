<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Services\System\OrderService;
use App\Http\Requests\System\Order\UpdateProductQuantityRequest;
use App\Traits\ApiResponseTrait;

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
    public function store()
    {
        return $this->respond(
            $this->service->placeOrder(),
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
        return $this->respond($this->service->cancelOrder($id), 'Cancelled');
    }

    /*
    |----------------------------------------
    | SHOW ORDER
    |----------------------------------------
    */
    public function show($id)
    {
        return $this->respond($this->service->show($id), 'OK');
    }

    /*
    |----------------------------------------
    | UPDATE PRODUCT QUANTITY
    |----------------------------------------
    */
    public function updateProductQuantity(UpdateProductQuantityRequest $request, $orderId)
    {
        return $this->respond(
            $this->service->updateProductQuantity(
                $orderId,
                $request->product_id,
                $request->quantity
            ),
            'Updated'
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
