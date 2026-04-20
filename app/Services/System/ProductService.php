<?php

namespace App\Services\System;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\UploadPicturesTrait;
use App\Traits\FcmService;

class ProductService
{
    use UploadPicturesTrait, FcmService;

    public function getAll()
    {
        $products = Product::with('image')->get();

        if ($products->isEmpty()) {
            return [];
        }

        return $products;
    }

    public function get($id)
    {
        return Product::with('image')->find($id);
    }

    public function create(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $product = Product::create([
                'name' => $request->name,
                'discraption' => $request->discraption,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'store_id' => $request->store_id,
            ]);

            if ($request->hasFile('image_path')) {

                $originalName = $request->file('image_path')->getClientOriginalName();
                $fileName = Str::uuid() . '_' . $originalName;

                $request->file('image_path')
                    ->move(public_path('uploads/products'), $fileName);

                $url = url("uploads/products/$fileName");

                $product->image()->create([
                    'image_path' => $url,
                ]);
            }

            $this->sendNotificationToAllUsers(
                'New Product Added',
                'Check out the latest product: ' . $product->name,
                [
                    'type' => 'new_product',
                    'product_id' => $product->id,
                ]
            );

            return $product;
        });
    }
}