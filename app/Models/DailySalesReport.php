<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesReport extends Model
{
    protected $fillable = [
        'report_date',
        'total_orders',
        'approved_orders',
        'rejected_orders',
        'pending_orders',
        'total_revenue',
        'total_items_sold',
        'chunks_processed',
    ];

    protected $casts = [
        'report_date'    => 'date',
        'total_revenue'  => 'float',
    ];
}