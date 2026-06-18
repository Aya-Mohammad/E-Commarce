<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CheckUserRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use App\Traits\AuthResponseTrait;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponseTrait, AuthResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function checkUser(CheckUserRequest $request)
    {
        $status = $this->authService->checkUser($request->phone);
        return $this->apiResponse(['status' => $status], 'User status fetched');
    }

    public function login(LoginRequest $request)
    {
        Log::info("LOGIN REQUEST RECEIVED");
        $result = $this->authService->login(
            $request->validated()
        );
        Log::info("LOGIN RESPONSE RETURNED");
        return $this->createNewToken(
            $result['token'],
            $result['user']
        );
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path');
        }
        $result = $this->authService->register($data);
        return $this->createNewToken($result['token'], $result['user']);
    }

    public function logout()
    {
        $loggedOut = $this->authService->logout();
        if (!$loggedOut) {
            return $this->apiResponse(null, 'Invalid or missing token', 401);
        }
        return $this->apiResponse(null, 'Logged out successfully');
    }

    public function refresh()
    {
        return $this->refreshToken();
    }
}