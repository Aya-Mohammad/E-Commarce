<?php

namespace App\Traits;

trait AuthResponseTrait
{
    public function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user(),
        ]);
    }

    public function refreshToken()
    {
        $token = auth()->refresh();

        return $this->createNewToken($token);
    }
}