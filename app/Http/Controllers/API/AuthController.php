<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Services\AuthService;
use App\Traits\AuthResponseTrait;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LoginWithPasswordRequest;
use App\Http\Requests\Auth\RegisterRequest;

class AuthController extends Controller
{
    use AuthResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        $status = $this->authService->login($request->phone);

        return response()->json([ 'status' => $status ]);
    }

    public function loginWithPassword(LoginWithPasswordRequest $request)
    {
        $result = $this->authService->loginWithPassword($request->validated());

        if (!$result) { return response()->json([ 'message' => 'Invalid password' ], 401); }

        return $this->createNewToken($result['token']);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image_path')) { $data['image'] = $request->file('image_path'); }

        $result = $this->authService->register($data);

        return $this->createNewToken($result['token']);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->bearerToken());

        return response()->json([ 'message' => 'Logged out successfully' ]);
    }

    public function refresh()
    {
        return $this->refreshToken();
    }
}