<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\FCMService;

class Store extends Model
{
    protected $fillable = ['name', 'discraption', 'delivery_cost', 'distance', 'start_of_work', 'end_of_work'];
    public function image()
    {
        return $this->morphMany(Picture::class, 'imageable');
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
