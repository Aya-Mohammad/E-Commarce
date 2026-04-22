<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Services\System\ProductService;
use App\Traits\ApiResponseTrait;
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
            $this->service->index(),
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
            $this->service->show($id),
            'OK',
            200
        );
    }
}
