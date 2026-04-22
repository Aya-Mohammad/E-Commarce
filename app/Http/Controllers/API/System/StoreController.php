<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Services\System\StoreService;
use App\Traits\ApiResponseTrait;
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
    public function index()
    {
        return $this->apiResponse(
            $this->service->index(),
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
            $this->service->show($id),
            'OK',
            200
        );
    }
}
