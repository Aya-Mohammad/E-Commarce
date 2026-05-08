<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StoreService;
use App\Http\Requests\Admin\Store\StoreRequest;
use App\Http\Requests\Admin\Store\UpdateStoreRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected StoreService $storeService) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->apiResponse(
            $this->storeService->getAllStores($perPage),
            'Stores fetched successfully'
        );
    }

    public function store(StoreRequest $request)
    {
        try {
            $store = $this->storeService->createStore(
                $request->validated(),
                $request->file('images', [])
            );

            return $this->apiResponse($store, 'Store created successfully', 201);

        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Error creating store', 500);
        }
    }

    public function show($id)
    {
        return $this->apiResponse(
            $this->storeService->getStoreById($id),
            'Store fetched successfully'
        );
    }

    public function update(UpdateStoreRequest $request, $id)
    {
        try {
            $store = $this->storeService->updateStore(
                $id,
                $request->validated(),
                $request->file('images', [])
            );

            return $this->apiResponse($store, 'Store updated successfully');

        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Error updating store', 500);
        }
    }

    public function destroy($id)
    {
        $this->storeService->deleteStore($id);

        return $this->apiResponse(null, 'Store deleted successfully');
    }
}