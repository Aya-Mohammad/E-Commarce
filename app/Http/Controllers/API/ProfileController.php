<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Http\Requests\UpdateProfileRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use JWTAuth;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function getUser()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->apiResponse(null, 'User not found', 404);
            }

            $data = $this->profileService->getUser($user);

            return $this->apiResponse(['user' => $data], 'User fetched successfully');

        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Token is invalid or expired', 401);
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        $updatedUser = $this->profileService->updateProfile($request, $user);

        return $this->apiResponse($updatedUser, 'Profile updated successfully');
    }
}
