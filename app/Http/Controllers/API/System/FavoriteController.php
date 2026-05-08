<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Services\System\FavoriteService;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\System\Favorite\AddFavoriteRequest;
use App\Http\Requests\System\Favorite\RemoveFavoriteRequest;
use App\Http\Requests\System\Favorite\CheckFavoriteRequest;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private FavoriteService $service) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->apiResponse(
            $this->service->getFavorites($perPage),
            'Favorites retrieved successfully',
            200
        );
    }

    public function check(CheckFavoriteRequest $request)
    {
        $result = $this->service->isFavorite($request->product_id);

        return $this->apiResponse(
            ['is_favorite' => $result],
            'Checked successfully',
            200
        );
    }

    public function store(AddFavoriteRequest $request)
    {
        $result = $this->service->addToFavorite($request->product_id);

        return $this->apiResponse(
            $result['data'] ?? null,
            $result['message'],
            $result['status'] ? $result['code'] : $result['code']
        );
    }

    public function destroy(RemoveFavoriteRequest $request)
    {
        $result = $this->service->removeFromFavorite($request->product_id);

        return $this->apiResponse(
            null,
            $result['message'],
            $result['code']
        );
    }
}