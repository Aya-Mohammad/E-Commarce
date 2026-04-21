<?php

namespace Tests\Unit;

use App\Http\Middleware\JwtMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddlewareTest extends TestCase
{
    public function test_middleware_binds_the_authenticated_api_user_to_the_request(): void
    {
        $user = new User([
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => 963123456789,
            'location' => 'Damascus',
        ]);

        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturnSelf();

        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturn($user);

        $request = Request::create('/api/profile', 'GET');
        $middleware = new JwtMiddleware();

        $response = $middleware->handle($request, function (Request $request) use ($user) {
            $this->assertSame($user, $request->user());

            return response()->noContent();
        });

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_middleware_returns_unauthorized_when_the_api_guard_has_no_user(): void
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturnSelf();

        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturnNull();

        $request = Request::create('/api/profile', 'GET');
        $middleware = new JwtMiddleware();

        $response = $middleware->handle($request, fn () => response()->noContent());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'data' => null,
            'message' => 'Unauthorized',
            'errors' => [],
        ], $response->getData(true));
    }
}
