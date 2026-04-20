<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

use App\Services\System\CartService;
use App\Http\Requests\System\Cart\AddToCartRequest;
use App\Http\Requests\System\Cart\MoveFavoriteRequest;
use Illuminate\Http\Request;

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
        return $this->apiResponse(
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
        return $this->apiResponse(
            $this->service->remove($id),
            'Removed',
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
        return $this->apiResponse(
            $this->service->moveFavorite($request->validated(), $favoriteId),
            'Moved',
            200
        );
    }
}