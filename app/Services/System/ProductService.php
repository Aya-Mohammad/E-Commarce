<?php

namespace App\Services\System;

use App\Models\Product;

class ProductService
{
    public function index()
    {
        $products = Product::with('image')->get();

        if ($products->isEmpty()) {
            return [];
        }

        return $products;
    }

    public function show($id)
    {
        return Product::with('image')->find($id);
    }
}
