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
    # Add (Rate Limiting - no brute force protection for admin panel)
    # Critical: admin login with no rate limit is a serious security risk
    # Add (Async Notification - notify admin on new login (security alert))
    # Missing: no logging of admin login attempts (IP, time, device)
    public function login(array $data): ?array
    {
        if (!$token = Auth::guard('admin')->attempt($data)) {
            return null;
        }

        return [
            'token' => $token,
            'admin' => new AdminResource(Auth::guard('admin')->user()),
        ];
    }

    # Add (Async Queue - image processing should be done in background Job)
    # Risk: Orphan Files - image stored inside Transaction
    # same problem as AuthService::register()
    # Fix: store image AFTER DB commit
    # Risk: no restriction on who can register as admin
    # any request can create a new admin - missing authorization check
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

    # Missing: no token invalidation (JWT token remains valid after logout)
    # compare with AuthService::logout() which uses JWTAuth::invalidate()
    # this logout only clears the session, token still usable
    # Missing: no fcm_token clearing on admin logout
    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }

    # Add (Caching (Redis) - called on every authenticated request)
    # short TTL Cache per admin session
    public function getUser(): ?AdminResource
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return null;
        }

        return new AdminResource($admin);
    }
}