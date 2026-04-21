<?php

use Illuminate\Foundation\Application;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $apiErrorResponse = static function (string $message, int $status, array $errors = []) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $message,
                'errors' => $errors,
            ], $status);
        };

        $exceptions->render(function (BadRequestHttpException $e, Request $request) use ($apiErrorResponse) {
            if ($request->is('api/*')) {
                return $apiErrorResponse('Invalid request payload or headers.', 400);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($apiErrorResponse) {
            if ($request->is('api/*')) {
                return $apiErrorResponse('Validation failed', 422, $e->errors());
            }
        });

        $exceptions->render(function (UnauthorizedHttpException $e, Request $request) use ($apiErrorResponse) {
            if ($request->is('api/*')) {
                return $apiErrorResponse('Unauthorized', 401);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($apiErrorResponse) {
            if ($request->is('api/*')) {
                return $apiErrorResponse('Unauthorized', 401);
            }
        });

        $exceptions->render(function (TokenExpiredException|TokenInvalidException|JWTException $e, Request $request) use ($apiErrorResponse) {
            if ($request->is('api/*')) {
                return $apiErrorResponse('Invalid or expired token', 401);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($apiErrorResponse) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error';

                return $apiErrorResponse($message, $status);
            }
        });
    })->create();

