<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\OrderService;
use App\Http\Requests\Admin\Order\HandleOrderRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected OrderService $orderService) {}

    public function handleOrder(HandleOrderRequest $request, $orderId)
    {
        try {
            $result = $this->orderService->handleOrder(
                $orderId,
                $request->validated()['action']
            );

            if (isset($result['error'])) {
                return $this->apiResponse(null, $result['error'], $result['status']);
            }

            $message = $request->action === 'approve' ? 'Order approved successfully' : 'Order rejected successfully';

            return $this->apiResponse($result['data'], $message);

        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Failed to process order', 500);
        }
    }

    public function getAllOrders(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->apiResponse(
            $this->orderService->getAllOrders($request->status, $perPage),
            'Orders fetched successfully'
        );
    }

    public function show($id)
    {
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return $this->apiResponse(null, 'Order not found', 404);
        }

        return $this->apiResponse($order, 'Order fetched successfully');
    }
}