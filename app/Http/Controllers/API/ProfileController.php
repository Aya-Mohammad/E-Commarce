<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Http\Requests\UpdateProfileRequest;
use App\Traits\ApiResponseTrait;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ProfileService $profileService) {}

    public function getUser()
    {
        $user = auth()->user();

        if (!$user) {
            return $this->apiResponse(null, 'User not found', 404);
        }

        return $this->apiResponse(
            ['user' => $this->profileService->getUser($user)],
            'User fetched successfully'
        );
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        $data = $request->validated();

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path');
        }

        $updatedUser = $this->profileService->updateProfile($data, $user);

        return $this->apiResponse($updatedUser, 'Profile updated successfully');
    }
}