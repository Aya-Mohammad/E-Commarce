<?php

use Illuminate\Foundation\Application;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->JwtMiddleware([
        //     'jwt.auth' => App\Http\Middleware\JwtMiddleware::class,
        // ]);

        //  $middleware->append([
        //     'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,

        //      //'auth' => \App\Http\Middleware\Authenticate::class,
        //        // 'auth:api' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
        // ]);
        // $middleware->appendToGroup('checkAdmin',[
        //     checkAdmin::class]);




    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

