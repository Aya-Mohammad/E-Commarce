<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\Store;
use App\Models\Picture;
use Illuminate\Database\Eloquent\Model;
use App\Models\FavouriteOfProduct;
use App\Services\FCMService;
class Product extends Model
{
    protected $fillable = ['name', 'discraption', 'price', 'quantity', 'store_id'];

    public function image()
    {
        return $this->morphMany(Picture::class, 'imageable');
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    public function  favouriteofproduct()
    {
        return $this->hasMany(FavouriteOfProduct::class);
    }
    public function deteilsoforder()
    {
        return $this->hasMany(deteilsoforder::class);
    }

    // app/Models/Product.php
    //new
    // public function orders()
    // {
    //     return $this->belongsToMany(Order::class, 'deteils_of_orders')
    //                 ->withPivot('quantity')
    //                 ->withTimestamps();
    // }

    public function orders()
    {
        return $this->belongsToMany(Order::class)->withPivot('quantity');
    }
    
}
