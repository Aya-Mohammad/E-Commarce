<?php

namespace App\Services\Admin;

use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreService
{
    public function getAllStores()
    {
        $stores = Store::with('image')->get();

        return response()->json(['stores' => $stores]);
    }

    public function createStore($request)
    {
        DB::beginTransaction();

        try {
            $store = Store::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/stores'), $fileName);

                    $store->image()->create([
                        'image_path' => url("uploads/stores/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Store created',
                'store' => $store
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating store',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStore($id, $request)
    {
        $store = Store::findOrFail($id);

        DB::beginTransaction();

        try {
            $store->update([
                'name' => $request->name ?? $store->name,
                'description' => $request->description ?? $store->description,
            ]);

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $img) {
                    $fileName = Str::uuid() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('uploads/stores'), $fileName);

                    $store->image()->create([
                        'image_path' => url("uploads/stores/$fileName"),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Store updated',
                'store' => $store
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error updating store',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteStore($id)
    {
        $store = Store::findOrFail($id);

        $store->delete();

        return response()->json([
            'message' => 'Store deleted'
        ]);
    }
}