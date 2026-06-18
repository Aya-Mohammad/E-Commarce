<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitorMiddleware
{
    private array $channelMap = [
        'order_race'      => ['log' => 'order_race',      'aop' => 'order_race_aop'],
        'order_duplicate' => ['log' => 'order_duplicate', 'aop' => 'order_duplicate_aop'],
        'order_stress'    => ['log' => 'order_stress',    'aop' => 'order_stress_aop'],
        'cart_race'       => ['log' => 'cart_race',       'aop' => 'cart_race_aop'],
        'cart_stress'     => ['log' => 'cart_stress',     'aop' => 'cart_stress_aop'],
        'auth_login'      => ['log' => 'auth_login',      'aop' => 'auth_login_aop'],
        'product_show'    => ['log' => 'product_show',    'aop' => 'product_show_aop'],
        'search'          => ['log' => 'search',          'aop' => 'search_aop'],
        'combined_100'    => ['log' => 'combined',        'aop' => 'combined_aop'], 
    ];

    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('perf_start_time', microtime(true));
        $request->attributes->set('perf_start_memory', memory_get_usage());

        $testType = $request->header('X-Test-Type', 'general');

        $request->attributes->set('test_type', $testType);

        app()->instance('perf.query_count', 0);

        if (!app()->bound('db.query.listener.registered')) {
            DB::listen(function () {
                $count = app()->bound('perf.query_count')
                    ? app('perf.query_count')
                    : 0;

                app()->instance('perf.query_count', $count + 1);
            });

            app()->instance('db.query.listener.registered', true);
        }

        $channels = $this->getChannels($request);

        Log::channel($channels['aop'])->info('AOP_START', [
            'url'       => $request->fullUrl(),
            'method'    => $request->method(),
            'test_type' => $testType,
            'time'      => now()->toDateTimeString(),
        ]);

        return $next($request);
    }

    public function terminate($request, $response)
    {
        $startTime   = $request->attributes->get('perf_start_time');
        $startMemory = $request->attributes->get('perf_start_memory');

        if (!$startTime) {
            return;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $memory = round(
            (memory_get_usage() - $startMemory) / 1024,
            2
        );

        $queryCount = app()->bound('perf.query_count')
            ? app('perf.query_count')
            : 0;

        $channels = $this->getChannels($request);

        Log::channel($channels['aop'])->info('AOP_TERMINATE_MONITOR', [
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'duration_ms' => $duration,
            'memory_kb'   => $memory,
            'query_count' => $queryCount,
            'status_code' => method_exists($response, 'getStatusCode')
                ? $response->getStatusCode()
                : 500,
            'timestamp'   => now()->toDateTimeString(),
        ]);
    }

    private function getChannels(Request $request): array
    {
        $testType = $request->header('X-Test-Type', 'general');
        $operation = $request->header('X-Operation');

        if ($testType === 'combined_100' && $operation) {

            return match ($operation) {
                'search' => [
                    'log' => 'combined_search',
                    'aop' => 'combined_search_aop',
                ],

                'product_show' => [
                    'log' => 'combined_product_show',
                    'aop' => 'combined_product_show_aop',
                ],

                'cart' => [
                    'log' => 'combined_cart',
                    'aop' => 'combined_cart_aop',
                ],

                'order' => [
                    'log' => 'combined_order',
                    'aop' => 'combined_order_aop',
                ],

                'auth_login' => [
                    'log' => 'combined_auth_login',
                    'aop' => 'combined_auth_login_aop',
                ],
                
                default => [
                    'log' => 'combined',
                    'aop' => 'combined_aop',
                ],
            };
        }

        return $this->channelMap[$testType] ?? [
            'log' => 'single',
            'aop' => 'performance',
        ];
    }
}