<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ProductService;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ProductService $productService) {}

    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->createProduct(
                $request->validated(),
                $request->file('images', [])
            );

            return $this->apiResponse($product, 'Product created successfully', 201);

        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Error creating product', 500);
        }
    }

    public function destroy($id)
    {
        $this->productService->deleteProduct($id);

        return $this->apiResponse(null, 'Product deleted successfully');
    }
}