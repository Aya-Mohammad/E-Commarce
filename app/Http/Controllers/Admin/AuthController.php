<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Services\Admin\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AuthService $service) {}

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
}