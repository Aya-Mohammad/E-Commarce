<?php

namespace App\Traits;
use Illuminate\Support\Facades\Auth;

trait AuthResponseTrait
{
    public function createNewToken($token, $user = null, $message = 'Authenticated successfully')
    {
        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $user ?? Auth::user(),
            ],
            'message' => $message,
            'errors' => [],
        ]);
    }

    public function refreshToken()
    {
        $token = auth()->refresh();

        return $this->createNewToken($token, null, 'Token refreshed successfully');
    }
}
