<?php

namespace Tests\Unit;

use App\Services\AuthService;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthServiceLogoutTest extends TestCase
{
    public function test_logout_invalidates_the_current_bearer_token(): void
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturnSelf();

        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->andReturnTrue();

        $this->assertTrue(app(AuthService::class)->logout());
    }

    public function test_logout_returns_false_when_the_bearer_token_is_missing(): void
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andThrow(new \RuntimeException('Token missing'));

        $this->assertFalse(app(AuthService::class)->logout());
    }
}
