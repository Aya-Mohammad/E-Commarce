<?php

namespace App\Services\Admin;

use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreService
{
    # Add (Caching (Redis) - store list is static data, high read frequency)
    # Add (Cache Invalidation - when store is created/updated/deleted)
    # Pagination already exists 
    public function getAllStores(int $perPage = 15)
    {
        return Store::with('image')->paginate($perPage);
    }

    # Add (Async Queue - image processing should be done in background Job)
    # Add (Cache Invalidation - invalidate store list cache after creation)
    # Risk: Orphan Files - images stored inside Transaction, if Transaction fails
    # DB rolls back but files remain on disk
    # Fix: store images AFTER DB commit, not inside Transaction
    public function createStore(array $data, array $images = []): Store
    {
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per product');
        }

        DB::beginTransaction();

        try {
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

                $fileName = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());

                $path = $img->storeAs('uploads/stores', $fileName, 'private');

                $store->image()->create(['image_path' => $path]);
            }

            DB::commit();

            return $store->load('image');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating store: ' . $e->getMessage());
            throw $e;
        }
    }

    # Add (Async Queue - image processing should be done in background Job)
    # Add (Cache Invalidation - invalidate store cache after update)
    # Risk: Orphan Files - same problem as createStore()
    # Risk: Storage Leak - old images not deleted when new ones uploaded
    public function updateStore(int $id, array $data, array $images = []): Store
    {
        $store = Store::findOrFail($id);

        if (count($images) > 5) {
        throw new \Exception('Maximum 5 images allowed per product');
    }

        DB::beginTransaction();

        try {
            $store->update(array_filter(
                array_intersect_key($data, array_flip([
                    'name', 'description', 'delivery_cost',
                    'distance', 'start_of_work', 'end_of_work'
                ])),
                fn($value) => !is_null($value)
            ));

            foreach ($images as $img) {
                if (!in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }

                $fileName = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());
                $path     = $img->storeAs('uploads/stores', $fileName, 'private');

                $store->image()->create(['image_path' => $path]);
            }

            DB::commit();

            return $store->fresh('image');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating store: ' . $e->getMessage());
            throw $e;
        }
    }

    # Add (Caching (Redis) - single store data, good Cache candidate)
    # Add (Cache Invalidation - when store is updated or deleted)
    public function getStoreById(int $id): Store
    {
        return Store::with('image')->findOrFail($id);
    }

    # Add (Cache Invalidation - invalidate store cache and store list cache)
    # Add (Async Queue - image deletion from disk should be done in background Job)
    # Risk: if Storage::delete() fails, store is already deleted from DB (no Transaction)
    # Fix: wrap in Transaction and handle storage failure gracefully
    # Risk: no check if store has active products or pending orders before deletion
    # Deleting store will cascade delete products → may affect active orders
    public function deleteStore(int $id): void
    {
        $store = Store::with('image')->findOrFail($id);

        foreach ($store->image as $image) {
            Storage::disk('private')->delete($image->image_path);
        }

        $store->delete();
    }
}