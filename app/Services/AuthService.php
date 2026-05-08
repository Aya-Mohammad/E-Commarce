<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JWTAuth;

class AuthService
{
    # Add (Caching (Redis) - called before every login to check if user exists)
    # high frequency call, good Cache candidate with short TTL
    # Risk: Phone enumeration vulnerability - attacker can check if any phone is registered
    public function checkUser($phone)
    {
        $user = User::where('phone', $phone)->first();

        return $user ? 'existing_user' : 'new_user';
    }

    # Add (Rate Limiting - no protection against brute force attacks)
    # Add (Async Notification - notify user on new login via Queue)
    # Missing: no logging of login attempts (IP, device, time)
    # Missing: no handling of fcm_token update on each login
    public function login($credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            return null;
        }

        return [
            'token' => $token,
            'user'  => new UserResource(auth()->user()),
        ];
    }

    # Add (Cache Invalidation - if user list is cached anywhere)
    # Add (Async Queue - image processing should be done in background Job)
    # Risk: Orphan Files - image stored inside Transaction
    # if Transaction fails, DB rolls back but file remains on disk
    # Fix: store image AFTER DB commit
    # Risk: phone uniqueness not enforced here - relies only on DB constraint
    # Missing: no welcome notification after registration
    public function register($data)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'phone'      => $data['phone'],
                'password'   => Hash::make($data['password']),
                'location'   => $data['location'],
                'fcm_token'  => $data['fcm_token'] ?? null,
            ]);

            $url = null;

            if (isset($data['image_path'])) {
                $file = $data['image_path'];

                $realMimeType = $file->getMimeType();
                if (!in_array($realMimeType, ['image/jpeg', 'image/png'])) {
                    throw new \Exception('Invalid file type.');
                }

                $extension = $file->getClientOriginalExtension();
                $fileName  = Str::uuid() . '.' . strtolower($extension);

                $path = $file->storeAs("uploads/users/{$data['phone']}", $fileName, 'private');
                $url  = Storage::disk('private')->url($path);

                $user->image()->create([
                    'image_path' => $path, 
                ]);
            }

            $token = JWTAuth::fromUser($user);

            DB::commit();

            $user->load('image');

            return [
                'token'      => $token,
                'user'       => new UserResource($user),
                'image_path' => $url,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    # Add (Cache Invalidation - clear any user-specific cached data on logout)
    # Missing: no fcm_token clearing on logout (user will still receive notifications)
    # Missing: no logging of logout event
    public function logout(): bool
    {
        try {
            JWTAuth::parseToken()->invalidate(true);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}