<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    public function getUser($user)
    {
        return $user->load('image');
    }

    public function updateProfile($request, $user)
    {
        DB::beginTransaction();

        try {
            $user->update([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'location'   => $request->location,
            ]);

            if ($request->hasFile('image_path')) {

                if ($user->image && $user->image->image_path) {
                    Storage::disk('public')->delete($user->image->image_path);
                }

                $file = $request->file('image_path');
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();

                $path = $file->move(
                    public_path("uploads/users/{$user->phone}"),
                    $fileName
                );

                $url = url("uploads/users/{$user->phone}/$fileName");

                $user->image()->updateOrCreate(
                    [],
                    ['image_path' => $url]
                );
            }

            DB::commit();

            return $user->fresh('image');

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}