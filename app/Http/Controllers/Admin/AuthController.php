<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAuthService;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Requests\Admin\Auth\RegisterRequest;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    protected $service;

    public function __construct(AdminAuthService $service)
    {
        $this->service = $service;
    }

    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request->validated());

        if (!$result) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        return response()->json([
            'message' => 'Login successfully',
            'access_token' => $result['token'],
            'token_type' => 'Bearer',
            'admin' => $result['admin'],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->service->register($request);

            return response()->json([
                'message' => 'Account created successfully',
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
                'admin' => $result['admin'],
                'image_path' => $result['image']
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        $this->service->logout();

        return response()->json(['message' => 'Logout']);
    }

    public function me()
    {
        $admin = $this->service->getUser();

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        return response()->json([
            'admin' => $admin,
            'image_path' => $admin->image->image_path ?? null
        ]);
    }
}