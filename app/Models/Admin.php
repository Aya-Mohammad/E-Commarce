<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Model;

class Admin extends Authenticatable implements JWTSubject
{
    protected $fillable = ['name', 'username', 'email', 'phone', 'password'];

    public function image(): MorphOne
    {
        return $this->morphOne(Picture::class, 'imageable');
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
