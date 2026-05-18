<?php
 
namespace App\Services\Admin;
 
use App\Jobs\DeleteStoreImagesJob;
use App\Jobs\ProcessStoreImageJob;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
 
class StoreService
{
    /**
     * Cache Key Strategy (6)
     */
    private function storeListCacheKey(int $page): string
    {
        return "stores:page:{$page}";
    }
 
    private function storeDetailCacheKey(int $id): string
    {
        return "store:detail:{$id}";
    }
 
    public function getAllStores(int $perPage = 15)
    {
        $page = (int) request()->get('page', 1);
 
        /**
         * Distributed Caching (6)
         * Resource Management (2)
         */
        return Cache::remember($this->storeListCacheKey($page), now()->addHour(), function () use ($perPage) {
            return Store::with('image')->paginate($perPage);
        });
    }
 
    public function createStore(array $data, array $images = []): Store
    {
        /**
         * Capacity Control (2)
         */
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per store');
        }
 
        /**
         * ACID Transaction (8)
         */
        DB::beginTransaction();
 
        try {
            $storedPaths = [];
 
            $store = Store::create([
                'name'          => $data['name'],
                'description'   => $data['description'],
                'delivery_cost' => $data['delivery_cost'],
                'distance'      => $data['distance'],
                'start_of_work' => $data['start_of_work'],
                'end_of_work'   => $data['end_of_work'],
            ]);
 
            foreach ($images as $img) {
                // Data Integrity (1) 
                if (!in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }
 
                $fileName      = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());
                $path          = $img->storeAs('uploads/stores', $fileName, 'private');
                $storedPaths[] = $path;
 
                $store->image()->create(['image_path' => $path]);
            }
 
            DB::commit();
 
            /**
             * Async Queue (3)
             */
            foreach ($storedPaths as $path) {
                ProcessStoreImageJob::dispatch($path);
            }
 
            /**
             * Cache Invalidation (6)
             */
            $this->invalidateStoreListCache();
 
            return $store->load('image');
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating store: ' . $e->getMessage());
            throw $e;
        }
    }
 
    public function updateStore(int $id, array $data, array $images = []): Store
    {
        // Data Integrity (1) 
        $store = Store::findOrFail($id);
 
        // Capacity Control (2)
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per store');
        }
 
        /**
         * ACID Transaction (8)
         */
        DB::beginTransaction();
 
        try {
            $storedPaths = [];
 
            /**
             * array_filter + array_intersect_key:
             */
            $store->update(array_filter(
                array_intersect_key($data, array_flip([
                    'name', 'description', 'delivery_cost',
                    'distance', 'start_of_work', 'end_of_work',
                ])),
                fn($value) => !is_null($value)
            ));
 
            foreach ($images as $img) {
                // Data Integrity (1) 
                if (!in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }
 
                $fileName      = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());
                $path          = $img->storeAs('uploads/stores', $fileName, 'private');
                $storedPaths[] = $path;
 
                $store->image()->create(['image_path' => $path]);
            }
 
            DB::commit();
 
            // Async Queue (3) 
            foreach ($storedPaths as $path) {
                ProcessStoreImageJob::dispatch($path);
            }
 
            /**
             * Cache Invalidation (6)
             */
            Cache::forget($this->storeDetailCacheKey($id));
            $this->invalidateStoreListCache();
 
            return $store->fresh('image');
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating store: ' . $e->getMessage());
            throw $e;
        }
    }
 
    public function getStoreById(int $id): ?Store
    {
        // Capacity Control (2)
        if (!is_numeric($id) || (int) $id <= 0) {
            return null;
        }
 
        $id = (int) $id;
 
        /**
         * Distributed Caching (6)
         */
        return Cache::remember($this->storeDetailCacheKey($id), now()->addHours(24), function () use ($id) {
            return Store::with(['image', 'products'])->findOrFail($id);
        });
    }
 
    public function deleteStore(int $id): bool
    {
        /**
         * Data Integrity (1)
         */
        $store = Store::withCount(['orders' => function ($query) {
            $query->whereIn('status', ['pending', 'processing']);
        }])->findOrFail($id);
 
        if ($store->orders_count > 0) {
            throw new \Exception('Cannot delete store with active pending orders.');
        }
 
        /**
         * ACID Transaction (8)
         */
        DB::beginTransaction();
 
        try {
            $imagePaths = $store->image->pluck('image_path')->toArray();
 
            $store->delete();
 
            DB::commit();
 
            /**
             * Async Queue (3)
             */
            if (!empty($imagePaths)) {
                DeleteStoreImagesJob::dispatch($imagePaths);
            }
 
            // Cache Invalidation (6)
            Cache::forget($this->storeDetailCacheKey($id));
            $this->invalidateStoreListCache();
 
            return true;
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting store: ' . $e->getMessage());
            throw $e;
        }
    }
 
    /**
     * Cache Invalidation (6)
     */
    private function invalidateStoreListCache(): void
    {
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget($this->storeListCacheKey($page));
        }
    }
}