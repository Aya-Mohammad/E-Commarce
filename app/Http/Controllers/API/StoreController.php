<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\StoreService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private StoreService $service) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->apiResponse(
            $this->service->index($perPage),
            'Stores retrieved successfully',
            200
        );
    }

    public function show($id)
    {
        $store = $this->service->show($id);

        if (!$store) {
            return $this->apiResponse(null, 'Store not found', 404);
        }

        return $this->apiResponse($store, 'Store retrieved successfully', 200);
    }
}