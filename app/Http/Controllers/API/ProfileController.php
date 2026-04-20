<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use JWTAuth;

class ProfileController extends Controller
{
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
                return response()->json(['message' => 'User not found'], 404);
            }

            $data = $this->profileService->getUser($user);

            return response()->json([
                'user' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token is invalid or expired'
            ], 401);
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        $updatedUser = $this->profileService->updateProfile($request, $user);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $updatedUser
        ]);
    }
}