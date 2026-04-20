<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Product;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string|max:255',
        ]);

        $query = $request->input('search');

        $stores = Store::with('image')
            ->where('name', 'like', "%{$query}%")
            ->get();

        $products = Product::with('image')
            ->where('name', 'like', "%{$query}%")
            ->get();

        return response()->json([
            'stores' => $stores,
            'products' => $products,
        ]);
    }
}