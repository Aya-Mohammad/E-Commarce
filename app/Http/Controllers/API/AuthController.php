<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use App\Traits\AuthResponseTrait;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LoginWithPasswordRequest;
use App\Http\Requests\Auth\RegisterRequest;

class AuthController extends Controller
{
    use ApiResponseTrait;
    use AuthResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        $status = $this->authService->login($request->phone);

        return $this->apiResponse(['status' => $status], 'Login status fetched');
    }

    public function loginWithPassword(LoginWithPasswordRequest $request)
    {
        $result = $this->authService->loginWithPassword($request->validated());

        if (!$result) { return $this->apiResponse(null, 'Invalid password', 401); }

        return $this->createNewToken($result['token'], $result['user']);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image_path')) { $data['image_path'] = $request->file('image_path'); }

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
