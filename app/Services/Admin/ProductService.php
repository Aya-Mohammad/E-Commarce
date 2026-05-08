<?php

namespace App\Services\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    # Add (Caching (Redis) - product list rarely changes, high read frequency)
    # Add (Cache Invalidation - when product is created/updated/deleted)
    # Pagination already exists 
    public function getAllProducts(int $perPage = 15)
    {
        return Product::with('image', 'store')->paginate($perPage);
    }

    # Add (Async Queue - image processing should be done in background Job)
    # Add (Cache Invalidation - invalidate product list cache after creation)
    # Risk: images are stored one by one inside Transaction - if storage fails
    # mid-loop, DB rolls back but already-stored files remain on disk (orphan files)
    # Fix: store images AFTER DB commit, not inside Transaction
    # Risk: no max limit on number of images per product (Capacity Control missing)
    public function createProduct(array $data, array $images = []): Product
    {
        DB::beginTransaction();

        try {
            $product = Product::create([
                'name'        => $data['name'],
                'description' => $data['description'],
                'price'       => $data['price'],
                'quantity'    => $data['quantity'],
                'store_id'    => $data['store_id'],
            ]);

            foreach ($images as $img) {
                $realMimeType = $img->getMimeType();
                if (!in_array($realMimeType, ['image/jpeg', 'image/png'])) {
                    continue;
                }

                $extension = strtolower($img->getClientOriginalExtension());
                $fileName  = Str::uuid() . '.' . $extension;

                $path = $img->storeAs('uploads/products', $fileName, 'private');

                $product->image()->create(['image_path' => $path]);
            }

            DB::commit();

            return $product->load('image', 'store');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating product: ' . $e->getMessage());
            throw $e;
        }
    }

    # Add (Async Queue - image processing should be done in background Job)
    # Add (Cache Invalidation - invalidate product cache after update)
    # Risk: same orphan files problem - storage inside Transaction
    # Risk: old images are NOT deleted when new ones are uploaded (storage leak)
    # Fix: delete old images from disk before storing new ones
    # Risk: no max limit on number of images (Capacity Control missing)
    public function updateProduct(int $id, array $data, array $images = []): Product
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            $product->update(array_filter(
                array_intersect_key($data, array_flip([
                    'name', 'description', 'price', 'quantity', 'store_id'
                ])),
                fn($value) => !is_null($value)
            ));

            foreach ($images as $img) {
                $realMimeType = $img->getMimeType();
                if (!in_array($realMimeType, ['image/jpeg', 'image/png'])) {
                    continue;
                }

                $extension = strtolower($img->getClientOriginalExtension());
                $fileName  = Str::uuid() . '.' . $extension;
                $path      = $img->storeAs('uploads/products', $fileName, 'private');

                $product->image()->create(['image_path' => $path]);
            }

            DB::commit();

            return $product->fresh('image', 'store');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating product: ' . $e->getMessage());
            throw $e;
        }
    }

    # Add (Caching (Redis) - single product data, very high read frequency)
    # Add (Cache Invalidation - when product is updated or deleted)
    public function getProductById(int $id): Product
    {
        return Product::with('image', 'store')->findOrFail($id);
    }

    # Add (Cache Invalidation - invalidate product cache and product list cache)
    # Add (Async Queue - image deletion from disk should be done in background Job)
    # Risk: if Storage::delete() fails, product is already deleted from DB
    # Fix: wrap in Transaction and handle storage failure gracefully
    # Risk: no check if product has active pending orders before deletion
    public function deleteProduct(int $id): void
    {
        $product = Product::with('image')->findOrFail($id);

        foreach ($product->image as $image) {
            Storage::disk('private')->delete($image->image_path);
        }

        $product->delete();
    }
}