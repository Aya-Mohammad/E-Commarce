<?php

namespace App\Services\Admin;

use App\Jobs\GenerateDailySalesReportJob;
use App\Models\DailySalesReport;
use Illuminate\Support\Facades\Cache;

class SalesReportService
{
    public function getReports(int $days = 30): mixed
    {
        $cacheKey = "admin:sales_reports:last_{$days}_days";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($days) {
            return DailySalesReport::orderBy('report_date', 'desc')
                ->limit($days)
                ->get();
        });
    }

    public function getReportByDate(string $date): ?DailySalesReport
    {
        $cacheKey = "admin:sales_report:{$date}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($date) {
            return DailySalesReport::where('report_date', $date)->first();
        });
    }

    public function triggerReport(string $date): void
    {
        GenerateDailySalesReportJob::dispatch($date)->onQueue('reports');

        Cache::forget("admin:sales_reports:last_30_days");
        Cache::forget("admin:sales_report:{$date}");
    }
}