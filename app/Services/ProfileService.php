<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    public function getUser($user)
    {
        return new UserResource($user->load('image'));
    }

    public function updateProfile(array $data, $user)
    {
        DB::beginTransaction();

        try {
            $user->update([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'location'   => $data['location'],
            ]);

            if (isset($data['image_path'])) {
                $file = $data['image_path'];

                $realMimeType = $file->getMimeType();
                if (!in_array($realMimeType, ['image/jpeg', 'image/png'])) {
                    throw new \Exception('Invalid file type.');
                }

                if ($user->image && $user->image->image_path) {
                    Storage::disk('private')->delete($user->image->image_path);
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $fileName  = Str::uuid() . '.' . $extension;

                $path = $file->storeAs(
                    "uploads/users/{$user->phone}",
                    $fileName,
                    'private'
                );

                $user->image()->updateOrCreate(
                    [],
                    ['image_path' => $path] 
                );
            }

            DB::commit();

            return new UserResource($user->fresh('image'));

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}