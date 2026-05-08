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

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->apiResponse(
            $this->service->index($perPage),
            'Products retrieved successfully',
            200
        );
    }

    public function show($id)
    {
        $product = $this->service->show($id);

        if (!$product) {
            return $this->apiResponse(null, 'Product not found', 404);
        }

        return $this->apiResponse($product, 'Product retrieved successfully', 200);
    }
}