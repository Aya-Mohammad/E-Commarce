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

    /*
    |----------------------------------------
    | ADD TO CART
    |----------------------------------------
    */
    public function add(AddToCartRequest $request)
    {
        return $this->respond(
            $this->service->add($request->validated()),
            'Added',
            200
        );
    }

    /*
    |----------------------------------------
    | REMOVE FROM CART
    |----------------------------------------
    */
    public function remove($id)
    {
        return $this->respond($this->service->remove($id), 'Removed', 200);
    }

    /*
    |----------------------------------------
    | UPDATE CART QUANTITY
    |----------------------------------------
    */
    public function updateQuantity(UpdateCartQuantityRequest $request, $id)
    {
        return $this->respond(
            $this->service->updateQuantity($id, $request->quantity),
            'Updated',
            200
        );
    }

    /*
    |----------------------------------------
    | SHOW CART
    |----------------------------------------
    */
    public function show()
    {
        return $this->apiResponse(
            $this->service->show(),
            'Cart data',
            200
        );
    }

    /*
    |----------------------------------------
    | MOVE FAVORITE TO CART
    |----------------------------------------
    */
    public function moveFavorite(MoveFavoriteRequest $request, $favoriteId)
    {
        return $this->respond(
            $this->service->moveFavorite($request->validated(), $favoriteId),
            'Moved',
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
