<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Picture;
use App\Models\Cart;
use Laravel\Sanctum\HasApiTokens;
use App\Models\DeviceToken;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject

{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['first_name', 'last_name', 'fcm_token','phone', 'password', 'location'];

    public function image(): MorphOne
    {
        return $this->morphOne(Picture::class, 'imageable');
    }
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function  favouriteofproduct()
    {
        return $this->hasOne(FavouriteOfProduct::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
