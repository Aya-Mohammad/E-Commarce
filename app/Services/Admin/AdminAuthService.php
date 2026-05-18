<?php

namespace App\Services\Admin;

use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminAuthService
{
    public function login(array $data): ?array
    {
        $key = 'admin_login_' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return ['error' => "Too many attempts. Try again in {$seconds}s", 'status' => 429];
        }

        if (!$token = Auth::guard('admin')->attempt($data)) {
            RateLimiter::hit($key, 300);
            return null;
        }

        RateLimiter::clear($key);

        return [
            'token' => $token,
            'admin' => new AdminResource(Auth::guard('admin')->user()),
        ];
    }

    public function register(array $data): array
    {
        DB::beginTransaction();

        try {
            $admin = Admin::create([
                'name'     => $data['name'],
                'username' => $data['username'],
                'email'    => $data['email'],
                'phone'    => $data['phone'],
                'password' => Hash::make($data['password']),
            ]);

            $imagePath = null;

            if (isset($data['image_path'])) {
                $file = $data['image_path'];

                $realMimeType = $file->getMimeType();
                if (!in_array($realMimeType, ['image/jpeg', 'image/png'])) {
                    throw new \Exception('Invalid file type.');
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $fileName  = Str::uuid() . '.' . $extension;

                $imagePath = $file->storeAs(
                    "uploads/admins/{$data['phone']}",
                    $fileName,
                    'private'
                );

                $admin->image()->create([
                    'image_path' => $imagePath, 
                ]);
            }

            $token = Auth::guard('admin')->login($admin);

            DB::commit();

            $admin->load('image');

            return [
                'admin'      => new AdminResource($admin),
                'token'      => $token,
                'image_path' => $imagePath,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }

    public function getUser(): ?AdminResource
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return null;
        }

        return new AdminResource($admin);
    }
}