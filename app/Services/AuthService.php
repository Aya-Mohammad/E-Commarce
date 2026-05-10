<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JWTAuth;
use Illuminate\Support\Facades\RateLimiter;
use App\Jobs\ProcessUserImage;

class AuthService
{
    public function checkUser($phone)
    {
        $user = User::where('phone', $phone)->first();

        return $user ? 'existing_user' : 'new_user';
    }

// _____________________________________________________________________________________________________

    // public function login($credentials, $fcmToken = null)
    // {
    //     $ip = request()->ip();
    //     $key = 'login_attempts_' . $ip;

    //     // إذا تجاوز المحاولات، اقطع الطلب فوراً برمز 429
    //     if (RateLimiter::tooManyAttempts($key, 5)) {
    //         abort(429, 'Too many attempts.');
    //     }

    //     // محاولة الدخول
    //     if (!$token = JWTAuth::attempt($credentials)) {
    //         RateLimiter::hit($key, 60);
            
    //         // إذا البيانات خطأ، اقطع الطلب برمز 401
    //         abort(401, 'Invalid credentials.');
    //     }

    //     RateLimiter::clear($key);

    //     return [
    //         'token' => $token,
    //         'user'  => auth()->user(),
    //     ];
    // }

    // Test By Elias
    public function login($credentials, $fcmToken = null)
    {
        $ip = request()->ip();
        $key = 'login_attempts_' . $ip;

        // --- المرحلة 1: Rate Limiting (لا تزال موجودة للحماية) ---
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, 'Too many attempts. Protected by Rate Limiter.');
        }

        // --- المرحلة 2: Distributed Caching (Redis) ---
        $phone = $credentials['phone'];
        $cacheKey = "user_auth_data_{$phone}";

        $user = Cache::remember($cacheKey, 3600, function () use ($phone) {
            return \App\Models\User::where('phone', $phone)->first();
        });

        if (!$token = JWTAuth::attempt($credentials)) {
            RateLimiter::hit($key, 60);
            abort(401, 'Invalid credentials.');
        }

        RateLimiter::clear($key);

        if ($fcmToken && auth()->user()) {
            auth()->user()->update(['fcm_token' => $fcmToken]);
        }

        return [
            'token' => $token,
            'user'  => $user, 
        ];
    }

    # Round 3
    // public function login($credentials, $fcmToken = null)
    // {
    //     $ip = request()->ip();
    //     $key = 'login_attempts_' . $ip;

    //     // --- المرحلة 1: Rate Limiting ---
    //     if (RateLimiter::tooManyAttempts($key, 5)) {
    //         abort(429, 'Too many attempts.');
    //     }

    //     // --- المرحلة 2: Redis Caching ---
    //     $phone = $credentials['phone'];
    //     $cacheKey = "user_auth_data_{$phone}";
        
    //     $user = Cache::remember($cacheKey, 3600, function () use ($phone) {
    //         return \App\Models\User::where('phone', $phone)->first();
    //     });

    //     if (!$token = JWTAuth::attempt($credentials)) {
    //         RateLimiter::hit($key, 60);
    //         abort(401, 'Invalid credentials.');
    //     }

    //     RateLimiter::clear($key);

    //     // --- المرحلة 3: Async Jobs (العمليات الخلفية) ---
    //     \App\Jobs\SendLoginNotification::dispatch($user, request()->userAgent());

    //     if ($fcmToken) {
    //         auth()->user()->update(['fcm_token' => $fcmToken]);
    //     }

    //     return [
    //         'token' => $token,
    //         'user'  => $user,
    //     ];
    // }
// _____________________________________________________________________________________
    // public function register($data)
    // {
    //     $filePath = null;

    //     try {
    //         $user = DB::transaction(function () use ($data) {

    //         $user = User::create([
    //             'first_name' => $data['first_name'],
    //             'last_name'  => $data['last_name'],
    //             'phone'      => $data['phone'],
    //             'password'   => Hash::make($data['password']),
    //             'location'   => $data['location'],
    //             'fcm_token'  => $data['fcm_token'] ?? null,
    //         ]);

    //         return $user;
    //     });

    //         if (isset($data['image_path'])) {

    //         $file = $data['image_path'];

    //         $mime = $file->getMimeType();

    //         if (!in_array($mime, ['image/jpeg', 'image/png'])) {
    //             throw new \Exception('Invalid file type.');
    //         }


    //         $fileName = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());

    //         $filePath = $file->storeAs(
    //             "uploads/users/{$user->phone}",
    //             $fileName,
    //             'private'
    //         );

    //         $user->image()->create([
    //             'image_path' => $filePath,
    //         ]);
    //     }

    //     $token = JWTAuth::fromUser($user);

    //     $user->load('image');

    //     return [
    //         'token'      => $token,
    //         'user'       => new UserResource($user),
    //         'image_path' => $filePath,
    //     ];

    //     } catch (\Throwable $e) {

    //         if ($filePath) {
    //             Storage::disk('private')->delete($filePath);
    //         }

    //         throw $e;
    //     }
    // }

    // public function register($data)
    // {
    //     try {
    //         $user = User::create([
    //             'first_name' => $data['first_name'],
    //             'last_name'  => $data['last_name'],
    //             'phone'      => $data['phone'],
    //             'password'   => Hash::make($data['password']),
    //             'location'   => $data['location'],
    //             'fcm_token'  => $data['fcm_token'] ?? null,
    //         ]);

    //         if (isset($data['image_path'])) {
    //             ProcessUserImage::dispatch($user, $data['image_path']);
    //         }

    //         $token = JWTAuth::fromUser($user);

    //         return [
    //             'token' => $token,
    //             'user'  => new UserResource($user),
    //         ];

    //     } catch (\Throwable $e) {
    //         throw $e;
    //     }
    // }

    public function register($data)
    {
        $hashedPassword = Hash::make($data['password']);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'phone'      => $data['phone'],
            'password'   => $hashedPassword,
            'location'   => $data['location'],
            'fcm_token'  => $data['fcm_token'] ?? null,
        ]);

        if (isset($data['image_path']) && $data['image_path'] instanceof \Illuminate\Http\UploadedFile) {
            $tempPath = $data['image_path']->store("uploads/users/{$user->phone}", 'public');
            ProcessUserImage::dispatch($user->id, $tempPath)->onQueue('images');
        }

        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token,
            'user'  => new UserResource($user),
        ];
    }

#____________________________________________________________________________________________
    
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