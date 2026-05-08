<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Requests\Admin\Auth\RegisterRequest;
use App\Services\Admin\AdminAuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AdminAuthService $service) {}

    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request->validated());

        if (!$result) {
            return $this->apiResponse(null, 'Invalid email or password', 401);
        }

        return $this->apiResponse([
            'access_token' => $result['token'],
            'token_type'   => 'Bearer',
            'admin'        => $result['admin'],
        ], 'Logged in successfully');
    }

    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('image_path')) {
                $data['image_path'] = $request->file('image_path');
            }

            $result = $this->service->register($data);

            return $this->apiResponse([
                'access_token' => $result['token'],
                'token_type'   => 'Bearer',
                'admin'        => $result['admin'],
                'image_path'   => $result['image_path'],
            ], 'Account created successfully', 201);

        } catch (\Exception $e) {
            Log::error('Admin registration failed: ' . $e->getMessage());

            return $this->apiResponse(null, 'Error creating account', 500);
        }
    }

    public function logout()
    {
        $this->service->logout();

        return $this->apiResponse(null, 'Logged out successfully');
    }

    public function me()
    {
        $admin = $this->service->getUser();

        if (!$admin) {
            return $this->apiResponse(null, 'Admin not found', 404);
        }

        return $this->apiResponse([
            'admin'      => $admin,
            'image_path' => $admin->image->image_path ?? null,
        ], 'Admin fetched successfully');
    }
}