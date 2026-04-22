<?php

namespace App\Services\System;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreService
{
    public function index()
    {
        return Store::with('image')->get();
    }

    public function show($id)
    {
        return Store::with('image')->with('products')->findOrFail($id);
    }
}
