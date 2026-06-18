<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Picture extends Model
{
    protected $fillable = ['image_path'];

    // تعريف العلاقة العكسية مع النماذج الأخرى
    public function imageable()
    {
        return $this->morphTo();
    }
}
