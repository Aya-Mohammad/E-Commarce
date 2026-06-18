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
    private function storeListCacheKey(int $page): string
    {
        return "stores:page:{$page}";
    }
 
    private function storeDetailCacheKey(int $id): string
    {
        return "store:detail:{$id}";
    }
 
    public function createStore(array $data, array $images = []): Store
    {
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per store');
        }

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
                if (!in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }
 
                $fileName      = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());
                $path          = $img->storeAs('uploads/stores', $fileName, 'private');
                $storedPaths[] = $path;
 
                $store->image()->create(['image_path' => $path]);
            }
 
            DB::commit();

            foreach ($storedPaths as $path) {
                ProcessStoreImageJob::dispatch($path);
            }

            $this->invalidateStoreListCache();
 
            return $store->load('image');
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating store: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteStore(int $id): bool
    {

        $store = Store::withCount(['orders' => function ($query) {
            $query->whereIn('status', ['pending', 'processing']);
        }])->findOrFail($id);
 
        if ($store->orders_count > 0) {
            throw new \Exception('Cannot delete store with active pending orders.');
        }

        DB::beginTransaction();
 
        try {
            $imagePaths = $store->image->pluck('image_path')->toArray();
 
            $store->delete();
 
            DB::commit();

            if (!empty($imagePaths)) {
                DeleteStoreImagesJob::dispatch($imagePaths);
            }
 
            Cache::forget($this->storeDetailCacheKey($id));
            $this->invalidateStoreListCache();
 
            return true;
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting store: ' . $e->getMessage());
            throw $e;
        }
    }

    private function invalidateStoreListCache(): void
    {
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget($this->storeListCacheKey($page));
        }
    }
}