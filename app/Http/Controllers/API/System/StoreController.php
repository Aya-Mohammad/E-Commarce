<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

use App\Services\System\StoreService;
use App\Http\Requests\System\Store\StoreRequest;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private StoreService $service) {}

    /*
    |----------------------------------------
    | GET ALL STORES
    |----------------------------------------
    */
    public function index(Request $request)
    {
        return $this->apiResponse(
            $this->service->getAll(),
            'Stores retrieved',
            200
        );
    }

    /*
    |----------------------------------------
    | GET SINGLE STORE
    |----------------------------------------
    */
    public function show($id)
    {
        return $this->apiResponse(
            $this->service->get($id),
            'OK',
            200
        );
    }

    /*
    |----------------------------------------
    | CREATE STORE
    |----------------------------------------
    */
    public function store(StoreRequest $request)
    {
        return $this->apiResponse(
            $this->service->create($request),
            'Store created',
            201
        );
    }
}