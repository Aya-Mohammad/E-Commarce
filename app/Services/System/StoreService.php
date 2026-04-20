<?php

namespace App\Services\System;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\UploadPicturesTrait;
use App\Traits\FcmService;

class StoreService
{
    use UploadPicturesTrait, FcmService;

    public function getAll()
    {
        return Store::with('image')->get();
    }

    public function get($id)
    {
        return Store::with('image')->find($id);
    }

    public function create(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $store = Store::create([
                'name' => $request->name,
                'discraption' => $request->discraption,
                'delivery_cost' => $request->delivery_cost,
                'distance' => $request->distance,
                'start_of_work' => $request->start_of_work,
                'end_of_work' => $request->end_of_work,
            ]);

            if ($request->hasFile('image_path')) {

                $originalName = $request->file('image_path')->getClientOriginalName();
                $fileName = Str::uuid() . '_' . $originalName;

                $request->file('image_path')
                    ->move(public_path('uploads/stores'), $fileName);

                $url = url("uploads/stores/$fileName");

                $store->image()->create([
                    'image_path' => $url,
                ]);
            }

            $this->sendNotificationToAllUsers(
                'متجر جديد',
                'تمت إضافة متجر جديد!',
                [
                    'type' => 'new_store',
                    'store_id' => $store->id,
                ]
            );

            return $store;
        });
    }
}