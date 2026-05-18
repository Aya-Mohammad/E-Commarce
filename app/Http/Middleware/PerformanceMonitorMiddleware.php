<?php

namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
 
/**
 * AOP — Aspect-Oriented Programming (Performance Monitor)
 */
class PerformanceMonitorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ===== نقطة الدخول (Before Advice) =====
        $startTime   = microtime(true);
        $startMemory = memory_get_usage();
        $queryCount  = 0;
 
        // نراقب كل استعلام DB يصير أثناء الـ Request
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });
 
        // ===== تنفيذ الـ Request الأصلي =====
        $response = $next($request);
 
        // ===== نقطة الخروج (After Advice) =====
        $duration    = round((microtime(true) - $startTime) * 1000, 2); 
        $memoryUsed  = round((memory_get_usage() - $startMemory) / 1024, 2); 
 
        // نسجل البيانات في الـ Log لمقارنة قبل وبعد
        Log::channel('performance')->info('AOP_MONITOR', [
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'user_id'      => auth()->id(),
            'duration_ms'  => $duration,
            'memory_kb'    => $memoryUsed,
            'query_count'  => $queryCount,
            'status_code'  => $response->getStatusCode(),
            'timestamp'    => now()->toDateTimeString(),
        ]);
 
        // نضيف Headers في الـ Response — تظهر في JMeter وPostman
        $response->headers->set('X-Response-Time-Ms', $duration);
        $response->headers->set('X-Query-Count', $queryCount);
        $response->headers->set('X-Memory-KB', $memoryUsed);
 
        return $response;
    }
}
