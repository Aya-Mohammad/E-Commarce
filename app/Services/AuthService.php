<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\RateLimiter;
use App\Jobs\ProcessUserImage;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendLoginNotification;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function checkUser(string $phone)
    {
        $user = User::where('phone', $phone)->first();
        return $user ? 'existing_user' : 'new_user';
    }

    public function register(array $data)
    {
        $hashedPassword = Hash::make($data['password']);
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'phone' => $data['phone'],
            'password' => $hashedPassword,
            'location' => $data['location'],
            'fcm_token' => $data['fcm_token'] ?? null,
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

    
    public function logout(): bool
    {
        try {
            JWTAuth::parseToken()->invalidate(true);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getLogChannel(): string
    {
        $testType = request()->header('X-Test-Type');
        $operation = request()->header('X-Operation');

        if ($testType === 'combined_100') {
            return match ($operation) {
                'auth_login' => 'combined_auth_login',
                default      => 'combined',
            };
        }

        return 'auth_login';
    }

    public function login(array $credentials, ?string $fcmToken = null)
    {
        if (config('app.strict_nfr_mode', false)) {
            return $this->optimizedLogin($credentials, $fcmToken);
        }
        return $this->legacyLogin($credentials, $fcmToken);
    }

    private function legacyLogin(array $credentials, ?string $fcmToken = null)
    {
        $channel = $this->getLogChannel();
        $ip  = request()->ip();
        $phone = $credentials['phone'];       
        $key = 'login_attempts_' . $ip . '_' . $phone;

        Log::channel($channel)->info("LOGIN START LEGACY | IP={$ip}");

        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::channel($channel)->warning("LOGIN BLOCKED BY RATE LIMIT");
            abort(429, 'Too many attempts.');
        }

        Log::channel($channel)->info("LOGIN DB QUERY START | PHONE={$phone}");
        $user = User::where('phone', $phone)->first();
        Log::channel($channel)->info("LOGIN DB QUERY END");

        if (!$token = JWTAuth::attempt($credentials)) {
            Log::channel($channel)->warning("LOGIN FAILED");
            RateLimiter::hit($key, 60);
            abort(401, 'Invalid credentials.');
        }

        Log::channel($channel)->info("LOGIN SUCCESS");
        RateLimiter::clear($key);
        SendLoginNotification::dispatch($user, request()->userAgent());

        if ($fcmToken && $user) {
            Log::channel($channel)->debug("UPDATING FCM TOKEN");
            $user->update([
                'fcm_token' => $fcmToken
            ]);
        }

        return ['token' => $token, 'user' => $user];
    }

    private function optimizedLogin(array $credentials, ?string $fcmToken = null)
    {
        $channel = $this->getLogChannel();
        $ip    = request()->ip();
        $phone = $credentials['phone'];           
        $key   = 'login_attempts_' . $ip . '_' . $phone;

        Log::channel($channel)->debug("LOGIN START OPTIMIZED | IP={$ip}");

        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::channel($channel)->debug("LOGIN RATE LIMIT HIT");
            abort(429, 'Too many attempts.');
        }

        $phone = $credentials['phone'];
        $cacheKey = "user_auth_data_{$phone}";

        Log::channel($channel)->debug("LOGIN CACHE CHECK");

        $user = Cache::remember(
            $cacheKey,
            600,
            function () use ($phone, $channel) { 
                Log::channel($channel)->debug('DB QUERY EXECUTED'); 
                return User::where('phone', $phone)->first();
            }
        );

        Log::channel($channel)->debug("LOGIN USER LOADED");

        if (!$token = JWTAuth::attempt($credentials)) {
            Log::channel($channel)->debug("LOGIN FAILED");
            RateLimiter::hit($key, 60);
            abort(401, 'Invalid credentials.');
        }

        Log::channel($channel)->debug("LOGIN SUCCESS");

        RateLimiter::clear($key);

        SendLoginNotification::dispatch($user, request()->userAgent());

        if ($fcmToken && $user) {

            Log::channel($channel)->debug("UPDATING FCM TOKEN");
            $user->update([
                'fcm_token' => $fcmToken
            ]);
        }

        Cache::forget($cacheKey);

        Log::channel($channel)->debug("LOGIN END");

        return [
            'token' => $token,
            'user' => $user
        ];
    }
}