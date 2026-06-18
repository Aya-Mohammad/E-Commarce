<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->integer('total_orders')->default(0);
            $table->integer('approved_orders')->default(0);
            $table->integer('rejected_orders')->default(0);
            $table->integer('pending_orders')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->integer('total_items_sold')->default(0);
            $table->integer('chunks_processed')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sales_reports');
    }
};