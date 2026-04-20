<?php

namespace App\Services\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function getAllProducts()
    {
        $products = Product::with('image', 'store')->get();

        return response()->json(['products' => $products]);
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

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/products'), $fileName);

                    $product->image()->create([
                        'image_path' => url("uploads/products/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
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

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/products'), $fileName);

                    $product->image()->create([
                        'image_path' => url("uploads/products/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        $product->delete();

        return response()->json([
            'message' => 'Product deleted'
        ]);
    }
}