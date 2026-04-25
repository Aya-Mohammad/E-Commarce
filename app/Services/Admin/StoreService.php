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
                'description' => $request->description,
                'delivery_cost' => $request->delivery_cost,
                'distance' => $request->distance,
                'start_of_work' => $request->start_of_work,
                'end_of_work' => $request->end_of_work,
            ]);

            if ($request->hasFile('image_path')) {

    $images = $request->file('image_path');
    $images = is_array($images) ? $images : [$images];

    foreach ($images as $img) {
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
        $validatedData = $request->validated();
        DB::beginTransaction();

        try {
            $store->update($validatedData);

            if ($request->hasFile('image_path')) {
                $images = $request->file('image_path');
                $images = is_array($images) ? $images : [$images];

                foreach ($images as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/stores'), $fileName);

                    $store->image()->create([
                        'image_path' => url("uploads/stores/$fileName"),
                    ]);
                }
            }

            DB::commit();

            // Reload the model from database to get fresh data
            $store = Store::with('image')->findOrFail($id);

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
