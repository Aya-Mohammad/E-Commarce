<?php

namespace App\Services\Admin;

use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
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
}