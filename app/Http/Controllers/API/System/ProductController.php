<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

use App\Services\System\ProductService;
use App\Http\Requests\System\Product\StoreProductRequest;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ProductService $service) {}

    /*
    |----------------------------------------
    | GET ALL PRODUCTS
    |----------------------------------------
    */
    public function index(Request $request)
    {
        return $this->apiResponse(
            $this->service->getAll(),
            'Products retrieved',
            200
        );
    }

    /*
    |----------------------------------------
    | GET SINGLE PRODUCT
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
    | ADD PRODUCT
    |----------------------------------------
    */
    public function store(StoreProductRequest $request)
    {
        return $this->apiResponse(
            $this->service->create($request),
            'Product created',
            201
        );
    }
}