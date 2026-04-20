<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ProductService;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        return $this->productService->getAllProducts();
    }

    public function store(StoreProductRequest $request)
    {
        return $this->productService->createProduct($request);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        return $this->productService->updateProduct($id, $request);
    }

    public function destroy($id)
    {
        return $this->productService->deleteProduct($id);
    }
}