<?php

namespace App\Services\Admin;

use App\Jobs\ProcessProductImageJob;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    // Caching (Redis) - product list rarely changes, high read frequency
    // Cache Invalidation - when product is created/updated/deleted
    // Pagination already exists
    public function getAllProducts(int $perPage = 15)
    {
        // return Product::with('image', 'store')->paginate($perPage);

        // =============== After Caching ===============
        // الحصول على رقم الصفحة الحالية ليكون جزءاً من مفتاح الكاش
        $page = request()->get('page', 1);

        // إنشاء مفتاح فريد لكل صفحة ولكل عدد عناصر (مثلاً: products_page_1_limit_15)
        $cacheKey = "products_page_{$page}_limit_{$perPage}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($perPage) {
            // جلب البيانات مع الصور بنظام الترقيم
            return Product::with('image', 'store')->paginate($perPage);
        });
    }

    // Add (Async Queue - image processing should be done in background Job)
    // Add (Cache Invalidation - invalidate product list cache after creation)
    // mid-loop, DB rolls back but already-stored files remain on disk (orphan files)
    public function createProduct(array $data, array $images = []): Product
    {
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per product');
        }

        DB::beginTransaction();

        try {
            $storedPaths = []; // لتتبع المسارات المؤقتة
            $store = null;

            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'quantity' => $data['quantity'],
                'store_id' => $data['store_id'],
            ]);

            foreach ($images as $img) {
                $realMimeType = $img->getMimeType();
                if (! in_array($realMimeType, ['image/jpeg', 'image/png'])) {
                    continue;
                }

                $extension = strtolower($img->getClientOriginalExtension());
                $fileName = Str::uuid().'.'.$extension;

                $path = $img->storeAs('uploads/products', $fileName, 'private');
                $storedPaths[] = $path; // نسجل المسار لضمان حذفه إذا فشل الـ Commit

                $product->image()->create(['image_path' => $path]);
            }

            DB::commit();
            // 2. بعد نجاح الـ Commit (خارج الـ Transaction)

            // إرسال الصور للمعالجة في الخلفية (تصغير الحجم، إضافة واترمارك، إلخ)
            foreach ($storedPaths as $path) {
                ProcessProductImageJob::dispatch($path);
            }

            // 3. تنظيف كاش القوائم (Cache Invalidation)
            Cache::forget('products_list_page_1');

            return $product->load('image', 'store');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating product: '.$e->getMessage());
            throw $e;
        }
    }

    // Async Queue - image processing should be done in background Job)
    // Cache Invalidation - invalidate product cache after update)
    // Risk: same orphan files problem - storage inside Transaction
    // Risk: old images are NOT deleted when new ones are uploaded (storage leak)
    public function updateProduct(int $id, array $data, array $images = []): Product
    {
        $product = Product::findOrFail($id);

        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per product');
        }

        DB::beginTransaction();

        try {
            $storedPaths = []; // لتتبع المسارات المؤقتة
            $store = null;

            $product->update(array_filter(
                array_intersect_key($data, array_flip([
                    'name', 'description', 'price', 'quantity', 'store_id',
                ])),
                fn ($value) => ! is_null($value)
            ));

            foreach ($images as $img) {
                $realMimeType = $img->getMimeType();
                if (! in_array($realMimeType, ['image/jpeg', 'image/png'])) {
                    continue;
                }

                $extension = strtolower($img->getClientOriginalExtension());
                $fileName = Str::uuid().'.'.$extension;
                $path = $img->storeAs('uploads/products', $fileName, 'private');
                $storedPaths[] = $path; // نسجل المسار لضمان حذفه إذا فشل الـ Commit

                $product->image()->create(['image_path' => $path]);
            }

            DB::commit();

            // 2. بعد نجاح الـ Commit (خارج الـ Transaction)
            // إرسال الصور للمعالجة في الخلفية (تصغير الحجم، إضافة واترمارك، إلخ)
            foreach ($storedPaths as $path) {
                ProcessProductImageJob::dispatch($path);
            }

            // 3. تنظيف كاش القوائم (Cache Invalidation)
            Cache::forget('product_details_'.$id);
            Cache::forget('products_list_page_1');

            return $product->fresh('image', 'store');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating product: '.$e->getMessage());
            throw $e;
        }
    }

    // Caching (Redis) - single product data, very high read frequency
    // Cache Invalidation - when product is updated or deleted
    public function getProductById(int $id): Product
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }
        // ========= Before Caching =========
        // return Product::with('image')->find((int) $id);
        // ==================================

        // ========= After Caching =========
        if (! is_numeric($id) || (int) $id <= 0) {
            return response()->json(['message' => 'Invalid ID'], 400);
        }

        $cacheKey = 'product_details_'.$id;
        $product = Cache::remember($cacheKey, now()->addDays(7), function () use ($id) {
            return Product::with('image')->findOrFail((int) $id);
        });

        return response()->json($product);
    }

    // Cache Invalidation - invalidate product cache and product list cache)
    // Async Queue - image deletion from disk should be done in background Job)
    // Risk: if Storage::delete() fails, product is already deleted from DB
    // Fix: wrap in Transaction and handle storage failure gracefully
    // Risk: no check if product has active pending orders before deletion
    public function deleteProduct(int $id)
    {
        // ======= Before Caching & Async Queue =======
        // $product = Product::with('image')->findOrFail($id);

        // foreach ($product->image as $image) {
        //     Storage::disk('private')->delete($image->image_path);
        // }

        // $product->delete();
        // ===========================================

        // ======== After logic =========
        // 1. Fetch product with count of active pending orders to prevent deletion if there are active orders
        $product = Product::withCount(['orders' => function ($query) {
            $query->whereIn('status', ['pending', 'processing']);
        }])->findOrFail($id);

        if ($product->orders_count > 0) {
            throw new \Exception('Cannot delete product with active pending orders.');
        }

        // 2. DB Transaction
        DB::beginTransaction();

        try {
            $imagePaths = $product->image->pluck('image_path')->toArray();
            $product->delete();

            // 4. إرسال مهمة حذف الصور للخلفية (Async Queue) لضمان سرعة الاستجابة
            if (! empty($imagePaths)) {
                DeleteProductImagesJob::dispatch($imagePaths);
            }
            DB::commit();

            Cache::forget('product_details_'.$id);
            Cache::forget('products_page_1');

            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: '.$e->getMessage());
            throw $e;
        }
    }
}
