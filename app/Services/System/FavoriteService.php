<?php

namespace App\Services\System;

use App\Models\Favourite;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class FavoriteService
{
    # Add (Caching (Redis) - favorites list per user, good Cache candidate)
    # Add (Cache Invalidation - when favorite is added or removed)
    # Pagination already exists 
    public function getFavorites(int $perPage = 15)
    {
        return Product::whereHas('Favourite', function ($query) {
            $query->where('user_id', auth()->id());
        })->with('image')->paginate($perPage);
    }

    # Add (Caching (Redis) - called frequently to check status, expensive if repeated)
    # ID Validation already exists 
    public function isFavorite($productId): bool
    {
        if (!is_numeric($productId) || (int) $productId <= 0) {
            return false;
        }

        return Favourite::where('user_id', auth()->id())
            ->where('product_id', (int) $productId)
            ->exists();
    }

    # Add (Cache Invalidation - invalidate user favorites cache after adding)
    # ID Validation already exists 
    public function addToFavorite($productId): array
    {
        if (!is_numeric($productId) || (int) $productId <= 0) {
            return ['status' => false, 'message' => 'Invalid product ID', 'code' => 422];
        }

        $product = Product::find((int) $productId);

        if (!$product) {
            return ['status' => false, 'message' => 'Product not found', 'code' => 404];
        }

        $fav = Favourite::firstOrCreate([
            'user_id'    => auth()->id(),
            'product_id' => $productId,
        ]);

        return [
            'status'  => true,
            'message' => 'Product added to favorites',
            'code'    => 201,
            'data'    => [
                'favorite' => $fav,
                'product'  => $product,
            ],
        ];
    }

    # Add (Cache Invalidation - invalidate user favorites cache after removing)
    # ID Validation already exists 
    public function removeFromFavorite($productId): array
    {
        if (!is_numeric($productId) || (int) $productId <= 0) {
            return ['status' => false, 'message' => 'Invalid product ID', 'code' => 422];
        }

        $fav = Favourite::where('user_id', auth()->id())
            ->where('product_id', (int) $productId)
            ->first();

        if (!$fav) {
            return ['status' => false, 'message' => 'Product not in favorites', 'code' => 404];
        }

        $fav->delete();

        return ['status' => true, 'message' => 'Product removed from favorites', 'code' => 200];
    }
}