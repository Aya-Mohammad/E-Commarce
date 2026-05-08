<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ProductService;
// ✅ إصلاح الاستيراد — Admin Requests وليس System
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ProductService $productService) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->apiResponse(
            $this->productService->getAllProducts($perPage),
            'Products fetched successfully'
        );
    }

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

    public function show($id)
    {
        return $this->apiResponse(
            $this->productService->getProductById($id),
            'Product fetched successfully'
        );
    }

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $product = $this->productService->updateProduct(
                $id,
                $request->validated(),
                $request->file('images', [])
            );

            return $this->apiResponse($product, 'Product updated successfully');

        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Error updating product', 500);
        }
    }

    public function destroy($id)
    {
        $this->productService->deleteProduct($id);

        return $this->apiResponse(null, 'Product deleted successfully');
    }
}