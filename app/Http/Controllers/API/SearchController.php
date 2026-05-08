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
            'search'   => 'required|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query   = $request->input('search');
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $perPage = $request->get('per_page', 15);

        $stores = Store::with('image')
            ->where('name', 'like', "%{$escaped}%")
            ->paginate($perPage, ['*'], 'stores_page');

        $products = Product::with('image')
            ->where('name', 'like', "%{$escaped}%")
            ->paginate($perPage, ['*'], 'products_page');

        return $this->apiResponse([
            'stores'   => $stores,
            'products' => $products,
        ], 'Search results fetched successfully');
    }
}