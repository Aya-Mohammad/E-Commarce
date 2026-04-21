<?php

namespace App\Services\Admin;

use App\Models\Store;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreService
{
    use ApiResponseTrait;

    public function getAllStores()
    {
        $stores = Store::with('image')->get();

        return $this->apiResponse(['stores' => $stores], 'Stores fetched successfully');
    }

    public function createStore($request)
    {
        DB::beginTransaction();

        try {
            $store = Store::create([
                'name' => $request->name,
                'discraption' => $request->discraption,
                'delivery_cost' => $request->delivery_cost,
                'distance' => $request->distance,
                'start_of_work' => $request->start_of_work,
                'end_of_work' => $request->end_of_work,
            ]);

            if ($request->hasFile('image_path')) {
                foreach ($request->file('image_path') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/stores'), $fileName);

                    $store->image()->create([
                        'image_path' => url("uploads/stores/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return $this->apiResponse([
                'store' => $store
            ], 'Store created');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->apiResponse(null, 'Error creating store', 500, ['exception' => [$e->getMessage()]]);
        }
    }

    public function updateStore($id, $request)
    {
        $store = Store::findOrFail($id);

        DB::beginTransaction();

        try {
            $store->update([
                'name' => $request->name ?? $store->name,
                'discraption' => $request->discraption ?? $store->discraption,
                'delivery_cost' => $request->delivery_cost ?? $store->delivery_cost,
                'distance' => $request->distance ?? $store->distance,
                'start_of_work' => $request->start_of_work ?? $store->start_of_work,
                'end_of_work' => $request->end_of_work ?? $store->end_of_work,
            ]);

            if ($request->hasFile('image_path')) {
                foreach ($request->file('image_path') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/stores'), $fileName);

                    $store->image()->create([
                        'image_path' => url("uploads/stores/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return $this->apiResponse([
                'store' => $store
            ], 'Store updated');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->apiResponse(null, 'Error updating store', 500, ['exception' => [$e->getMessage()]]);
        }
    }

    public function getStoreById($id)
    {
        $store = Store::with('image')->findOrFail($id);

        return $this->apiResponse(['store' => $store], 'Store fetched successfully');
    }

    public function deleteStore($id)
    {
        $store = Store::findOrFail($id);

        $store->delete();

        return $this->apiResponse(null, 'Store deleted');
    }
}
