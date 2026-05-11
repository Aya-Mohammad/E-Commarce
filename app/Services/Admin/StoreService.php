<?php

namespace App\Services\Admin;

use App\Jobs\DeleteStoreImagesJob;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreService
{
    // Caching (Redis) - store list is static data, high read frequency
    // Cache Invalidation - when store is created/updated/deleted
    // Pagination already exists
    public function getAllStores(int $perPage = 15)
    {
        // الحصول على رقم الصفحة الحالية ليكون جزءاً من مفتاح الكاش
        $page = request()->get('page', 1);

        // إنشاء مفتاح فريد لكل صفحة ولكل عدد عناصر (مثلاً: stores_page_1_limit_15)
        $cacheKey = "stores_page_{$page}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($perPage) {
            // جلب البيانات مع الصور بنظام الترقيم
            return Store::with('image')->paginate($perPage);
        });
    }

    // Async Queue - image processing should be done in background Job
    // Cache Invalidation - invalidate store list cache after creation
    // Risk: Orphan Files - images stored inside Transaction, if Transaction fails
    // DB rolls back but files remain on disk
    // Fix: store images AFTER DB commit, not inside Transaction
    public function createStore(array $data, array $images = []): Store
    {
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per store');
        }

        DB::beginTransaction();

        try {
            $storedPaths = []; // لتتبع المسارات المؤقتة
            $store = null;

            $store = Store::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'delivery_cost' => $data['delivery_cost'],
                'distance' => $data['distance'],
                'start_of_work' => $data['start_of_work'],
                'end_of_work' => $data['end_of_work'],
            ]);

            foreach ($images as $img) {
                if (! in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }

                $fileName = Str::uuid().'.'.strtolower($img->getClientOriginalExtension());

                $path = $img->storeAs('uploads/stores', $fileName, 'private');
                $storedPaths[] = $path; // نسجل المسار لضمان حذفه إذا فشل الـ Commit

                $store->image()->create(['image_path' => $path]);
            }

            DB::commit();
            // 2. بعد نجاح الـ Commit (خارج الـ Transaction)

            // إرسال الصور للمعالجة في الخلفية (تصغير الحجم، إضافة واترمارك، إلخ)
            foreach ($storedPaths as $path) {
                ProcessStoreImageJob::dispatch($path);
            }

            // 3. تنظيف كاش القوائم (Cache Invalidation)
            Cache::forget('stores_page_1');

            return $store->load('image');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating store: '.$e->getMessage());
            throw $e;
        }
    }

    // Async Queue - image processing should be done in background Job
    // Cache Invalidation - invalidate store cache after update
    // Risk: Orphan Files - same problem as createStore()
    // Risk: Storage Leak - old images not deleted when new ones uploaded
    public function updateStore(int $id, array $data, array $images = []): Store
    {
        $store = Store::findOrFail($id);

        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per product');
        }

        DB::beginTransaction();

        try {
            $storedPaths = []; // لتتبع المسارات المؤقتة
            $store = null;

            $store->update(array_filter(
                array_intersect_key($data, array_flip([
                    'name', 'description', 'delivery_cost',
                    'distance', 'start_of_work', 'end_of_work',
                ])),
                fn ($value) => ! is_null($value)
            ));

            foreach ($images as $img) {
                if (! in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }

                $fileName = Str::uuid().'.'.strtolower($img->getClientOriginalExtension());
                $path = $img->storeAs('uploads/stores', $fileName, 'private');
                $storedPaths[] = $path; // نسجل المسار لضمان حذفه إذا فشل الـ Commit

                $store->image()->create(['image_path' => $path]);
            }

            DB::commit();

            // 2. بعد نجاح الـ Commit (خارج الـ Transaction)
            // إرسال الصور للمعالجة في الخلفية (تصغير الحجم، إضافة واترمارك، إلخ)
            foreach ($storedPaths as $path) {
                ProcessStoreImageJob::dispatch($path);
            }

            // 3. تنظيف كاش القوائم (Cache Invalidation)
            Cache::forget('store_details_'.$id);
            Cache::forget('stores_page_1');

            return $store->fresh('image');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating store: '.$e->getMessage());
            throw $e;
        }
    }

    // Caching (Redis) - single store data, good Cache candidate
    // Cache Invalidation - when store is updated or deleted
    public function getStoreById(int $id): Store
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }
        // ========= Before Caching =========
        // return Store::with('image')->find((int) $id);
        // ==================================

        // ========= After Caching =========
        if (! is_numeric($id) || (int) $id <= 0) {
            return response()->json(['message' => 'Invalid ID'], 400);
        }

        $cacheKey = 'store_details_'.$id;
        $store = Cache::remember($cacheKey, now()->addDays(7), function () use ($id) {
            return Store::with(['image', 'products'])->findOrFail((int) $id);
        });

        return response()->json($store);
    }

    // Cache Invalidation - invalidate store cache and store list cache)
    // Async Queue - image deletion from disk should be done in background Job
    // Risk: if Storage::delete() fails, store is already deleted from DB (no Transaction)
    // Fix: wrap in Transaction and handle storage failure gracefully
    // Risk: no check if store has active products or pending orders before deletion
    // Deleting store will cascade delete products → may affect active orders
    public function deleteStore(int $id)
    {
        // ========= Before logic =========
        // $store = Store::with('image')->findOrFail($id);

        // foreach ($store->image as $image) {
        //     Storage::disk('private')->delete($image->image_path);
        // }
        // Cache::forget('store_details_'.$id);
        // Cache::forget('store_list');
        // $store->delete();
        // ================================

        // ======== After logic =========
        // 1. Fetch store with count of active pending orders to prevent deletion if there are active orders
        $store = Store::withCount(['orders' => function ($query) {
            $query->whereIn('status', ['pending', 'processing']);
        }])->findOrFail($id);

        if ($store->orders_count > 0) {
            throw new \Exception('Cannot delete store with active pending orders.');
        }

        // 2. DB Transaction
        DB::beginTransaction();

        try {
            $imagePaths = $store->image->pluck('image_path')->toArray();
            $store->delete();

            // 4. إرسال مهمة حذف الصور للخلفية (Async Queue) لضمان سرعة الاستجابة
            if (! empty($imagePaths)) {
                DeleteStoreImagesJob::dispatch($imagePaths);
            }
            DB::commit();

            Cache::forget('store_details_'.$id);
            Cache::forget('store_page_1');

            return response()->json(['message' => 'Store deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting store: '.$e->getMessage());
            throw $e;
        }
    }
}
