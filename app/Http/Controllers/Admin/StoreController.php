<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StoreService;
use App\Http\Requests\System\Store\StoreRequest;
use App\Http\Requests\System\Store\UpdateStoreRequest;

class StoreController extends Controller
{
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function index()
    {
        return $this->storeService->getAllStores();
    }

    public function store(StoreRequest $request)
    {
        return $this->storeService->createStore($request);
    }

    public function show($id)
    {
        return $this->storeService->getStoreById($id);
    }

    public function update(UpdateStoreRequest $request, $id)
    {
        
        return $this->storeService->updateStore($id, $request);
    }

    public function destroy($id)
    {
        return $this->storeService->deleteStore($id);
    }
}
