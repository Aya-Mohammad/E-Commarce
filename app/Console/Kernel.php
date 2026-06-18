<?php

namespace App\Console;

use App\Jobs\GenerateDailySalesReportJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $yesterday = now()->subDay()->format('Y-m-d');
            GenerateDailySalesReportJob::dispatch($yesterday)->onQueue('reports');
        })->dailyAt('02:00')->name('daily-sales-report')->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}