<?php

namespace App\Jobs;

use App\Models\DailySalesReport;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDailySalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly string $date
    ) {}

    public function handle(): void
    {
        Log::info("GenerateDailySalesReportJob START | date={$this->date}");

        $totalOrders    = 0;
        $approvedOrders = 0;
        $rejectedOrders = 0;
        $pendingOrders  = 0;
        $totalRevenue   = 0.0;
        $totalItems     = 0;
        $chunksCount    = 0;

        /**
         * NFR#4 — Batch Processing بـ chunk()
         *
         * بدل ما نجلب كل الـ Orders مرة واحدة في الذاكرة
         * نعالجها على دفعات (500 طلب في كل دفعة)
         * هذا يمنع memory overflow عند الأحجام الكبيرة
         */
        Order::with('items')
            ->whereDate('created_at', $this->date)
            ->chunk(500, function ($orders) use (
                &$totalOrders,
                &$approvedOrders,
                &$rejectedOrders,
                &$pendingOrders,
                &$totalRevenue,
                &$totalItems,
                &$chunksCount
            ) {
                $chunksCount++;

                Log::info("GenerateDailySalesReportJob | Processing chunk #{$chunksCount} | orders=" . $orders->count());

                foreach ($orders as $order) {
                    $totalOrders++;

                    match ($order->status) {
                        'approved', 'delivered' => $approvedOrders++,
                        'rejected', 'cancelled' => $rejectedOrders++,
                        default                 => $pendingOrders++,
                    };

                    // نحسب الإيرادات فقط من الطلبات المكتملة
                    if (in_array($order->status, ['approved', 'delivered'])) {
                        $totalRevenue += (float) $order->total_price;

                        foreach ($order->items as $item) {
                            $totalItems += $item->quantity;
                        }
                    }
                }
            });

        // حفظ أو تحديث التقرير اليومي
        DailySalesReport::updateOrCreate(
            ['report_date' => $this->date],
            [
                'total_orders'    => $totalOrders,
                'approved_orders' => $approvedOrders,
                'rejected_orders' => $rejectedOrders,
                'pending_orders'  => $pendingOrders,
                'total_revenue'   => $totalRevenue,
                'total_items_sold'=> $totalItems,
                'chunks_processed'=> $chunksCount,
            ]
        );

        Log::info("GenerateDailySalesReportJob DONE | date={$this->date} | chunks={$chunksCount} | orders={$totalOrders} | revenue={$totalRevenue}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("GenerateDailySalesReportJob FAILED | date={$this->date} | error=" . $e->getMessage());
    }
}