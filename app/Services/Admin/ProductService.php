<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    use ApiResponseTrait;

    public function getAllProducts()
    {
        $products = Product::with('image', 'store')->get();

        return $this->apiResponse(['products' => $products], 'Products fetched successfully');
    }

    public function createProduct($request)
    {
        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'store_id' => $request->store_id,
            ]);

            if ($request->hasFile('image_path')) {
                foreach ($request->file('image_path') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/products'), $fileName);

                    $product->image()->create([
                        'image_path' => url("uploads/products/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return $this->apiResponse([
                'product' => $product
            ], 'Product created');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->apiResponse(null, 'Error creating product', 500, ['exception' => [$e->getMessage()]]);
        }
    }

    public function updateProduct($id, $request)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            $product->update([
                'name' => $request->name ?? $product->name,
                'description' => $request->description ?? $product->description,
                'price' => $request->price ?? $product->price,
                'quantity' => $request->quantity ?? $product->quantity,
                'store_id' => $request->store_id ?? $product->store_id,
            ]);

            if ($request->hasFile('image_path')) {
                foreach ($request->file('image_path') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/products'), $fileName);

                    $product->image()->create([
                        'image_path' => url("uploads/products/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return $this->apiResponse([
                'product' => $product
            ], 'Product updated');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->apiResponse(null, 'Error updating product', 500, ['exception' => [$e->getMessage()]]);
        }
    }

    public function getProductById($id)
    {
        $product = Product::with('image', 'store')->findOrFail($id);

        return $this->apiResponse(['product' => $product], 'Product fetched successfully');
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        $product->delete();

        return $this->apiResponse(null, 'Product deleted');
    }
}
