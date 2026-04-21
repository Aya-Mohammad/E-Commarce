<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use JWTAuth;

class AuthService
{
    public function login($phone)
    {
        $user = User::where('phone', $phone)->first();

        return $user ? 'existing_user' : 'new_user';
    }

    public function loginWithPassword($credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) { return null; }

        return [
            'token' => $token,
            'user' => new UserResource(auth()->user())
        ];
    }

    public function register($data)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'location' => $data['location'],
                'fcm_token' => $data['fcm_token'] ?? null
            ]);

            $url = null;

            if (isset($data['image_path'])) {
                $file = $data['image_path'];
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
                $file->move(public_path("uploads/users/{$data['phone']}"), $fileName);
                $url = url("uploads/users/{$data['phone']}/$fileName");

                $user->image()->create([
                    'image_path' => $url,
                ]);
            }

            $token = JWTAuth::fromUser($user);

            DB::commit();

            // Refresh user to load the image relationship
            $user->load('image');

            return [
                'token' => $token,
                'user' => new UserResource($user),
                'image_path' => $url
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function logout(): bool
    {
        try {
            JWTAuth::parseToken()->invalidate();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
