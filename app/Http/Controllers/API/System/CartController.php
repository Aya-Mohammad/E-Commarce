<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Services\System\CartService;
use App\Http\Requests\System\Cart\AddToCartRequest;
use App\Http\Requests\System\Cart\MoveFavoriteRequest;
use App\Http\Requests\System\Cart\UpdateCartQuantityRequest;

class CartController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CartService $service) {}

    public function add(AddToCartRequest $request)
    {
        return $this->respond(
            $this->service->add($request->validated()),
            'Product added to cart successfully',
            200
        );
    }

    public function remove($id)
    {
        return $this->respond(
            $this->service->remove($id),
            'Product removed from cart successfully',
            200
        );
    }

    public function updateQuantity(UpdateCartQuantityRequest $request, $id)
    {
        return $this->respond(
            $this->service->updateQuantity($id, $request->quantity),
            'Cart quantity updated successfully',
            200
        );
    }

    public function show()
    {
        return $this->apiResponse(
            $this->service->show(),
            'Cart fetched successfully',
            200
        );
    }

    public function moveFavorite(MoveFavoriteRequest $request, $favoriteId)
    {
        return $this->respond(
            $this->service->moveFavorite($request->validated(), $favoriteId),
            'Item moved to cart successfully',
            200
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