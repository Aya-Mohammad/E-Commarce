<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Product;
use App\Traits\ApiResponseTrait;

class SearchController extends Controller
{
    use ApiResponseTrait;

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

        return $this->apiResponse([
            'stores' => $stores,
            'products' => $products,
        ], 'Search results fetched successfully');
    }
}
