<?php

namespace App\Services\Admin;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthService
{
    public function login($data)
    {
        if (!$token = Auth::guard('admin')->attempt($data)) {
            return null;
        }

        return [
            'token' => $token,
            'admin' => Auth::guard('admin')->user(),
        ];
    }

    public function register($request)
    {
        DB::beginTransaction();

        try {
            $admin = Admin::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $imageUrl = null;

            if ($request->hasFile('image_path')) {
                $originalName = $request->file('image_path')->getClientOriginalName();
                $fileName = Str::uuid() . '_' . $originalName;

                $request->file('image_path')->move(public_path("uploads/admin/$request->phone"), $fileName);

                $imageUrl = url("uploads/admin/$request->phone/$fileName");

                $admin->image()->create([
                    'image_path' => $imageUrl,
                ]);
            }

            $token = Auth::guard('admin')->login($admin);

            DB::commit();

            return [
                'admin' => $admin,
                'token' => $token,
                'image' => $imageUrl
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
    }

    public function getUser()
    {
        return Auth::guard('admin')->user();
    }
}