<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Requests\Admin\Auth\RegisterRequest;
use App\Services\Admin\AdminAuthService;
use App\Traits\ApiResponseTrait;

class AdminAuthController extends Controller
{
    use ApiResponseTrait;

    protected $service;

    public function __construct(AdminAuthService $service)
    {
        $this->service = $service;
    }

    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request->validated());

        if (! $result) {
            return $this->apiResponse(null, 'Invalid email or password', 401);
        }

        return $this->apiResponse([
            'access_token' => $result['token'],
            'token_type' => 'Bearer',
            'admin' => $result['admin'],
        ], 'Login successfully');
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->service->register($request);

            return $this->apiResponse([
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
                'admin' => $result['admin'],
                'image_path' => $result['image'],
            ], 'Account created successfully', 201);

        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Error creating account', 500, ['exception' => [$e->getMessage()]]);
        }
    }

    public function logout()
    {
        $this->service->logout();

        return $this->apiResponse(null, 'Logout');
    }

    public function me()
    {
        $admin = $this->service->getUser();

        if (! $admin) {
            return $this->apiResponse(null, 'Admin not found', 404);
        }

        return $this->apiResponse([
            'admin' => $admin,
            'image_path' => $admin->image->image_path ?? null,
        ], 'Admin fetched successfully');
    }
}
