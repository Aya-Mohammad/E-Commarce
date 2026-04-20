<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\System\FavoriteService;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\System\Favorite\AddFavoriteRequest;
use App\Http\Requests\System\Favorite\RemoveFavoriteRequest;
use App\Http\Requests\System\Favorite\CheckFavoriteRequest;

class FavoriteController extends Controller
{
    use ApiResponseTrait;

    private $service;

    public function __construct(FavoriteService $service)
    {
        $this->service = $service;
    }

    /*
    |----------------------------------------
    | GET ALL FAVORITES
    |----------------------------------------
    */
    public function index()
    {
        $data = $this->service->getFavorites();

        if (!$data) {
            return $this->apiResponse([], 'User not authenticated', 401);
        }

        return $this->apiResponse($data, 'Favorites retrieved', 200);
    }

    /*
    |----------------------------------------
    | CHECK FAVORITE
    |----------------------------------------
    */
    public function check(CheckFavoriteRequest $request)
    {
        $result = $this->service->isFavorited($request->product_id);

        return $this->apiResponse($result, 'Checked', 200);
    }

    /*
    |----------------------------------------
    | ADD FAVORITE
    |----------------------------------------
    */
    public function store(AddFavoriteRequest $request)
    {
        $result = $this->service->addToFavorite($request->product_id);

        if (!$result['status']) {
            return $this->apiResponse(null, $result['message'], 400);
        }

        return $this->apiResponse($result['data'] ?? null, $result['message'], 200);
    }

    /*
    |----------------------------------------
    | REMOVE FAVORITE
    |----------------------------------------
    */
    public function destroy(RemoveFavoriteRequest $request)
    {
        $result = $this->service->removeFromFavorite($request->product_id);

        if (!$result['status']) {
            return $this->apiResponse(null, $result['message'], 400);
        }

        return $this->apiResponse(null, $result['message'], 200);
    }
}