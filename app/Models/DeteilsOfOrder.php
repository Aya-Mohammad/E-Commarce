<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class DeteilsOfOrder extends Model
{
    protected $fillable = ['order_id', 'product_id', 'quantity'];
    public function orders()
    {
        return $this->belongsTo(Order::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
