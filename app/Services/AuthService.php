<?php

namespace App\Services;

use App\Models\User;
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
            'user' => auth()->user()
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

            if (isset($data['image'])) {
                $file = $data['image'];
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
                $file->move(public_path("uploads/users/{$data['phone']}"), $fileName);
                $url = url("uploads/users/{$data['phone']}/$fileName");

                $user->image()->create([
                    'image_path' => $url,
                ]);
            }

            $token = JWTAuth::fromUser($user);

            DB::commit();

            return [
                'token' => $token,
                'user' => $user,
                'image_path' => $url
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function logout($token)
    {
        JWTAuth::setToken($token)->invalidate();
    }
}