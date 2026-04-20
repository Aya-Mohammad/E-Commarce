<?php

namespace App\Services\System;

use App\Models\Product;
use App\Models\FavouriteOfProduct;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteService
{
    public function getFavorites()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return Product::whereHas('favouriteofproduct', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('image')->get();
    }

    public function isFavorited($productId)
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return FavouriteOfProduct::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();
    }

    public function addToFavorite($productId)
    {
        $user = Auth::user();

        if (!$user) {
            return ['status' => false, 'message' => 'User not authenticated'];
        }

        $product = Product::find($productId);

        if (!$product) {
            return ['status' => false, 'message' => 'Product not found'];
        }

        $existing = FavouriteOfProduct::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return ['status' => true, 'message' => 'This product already liked'];
        }

        $fav = FavouriteOfProduct::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return ['status' => true, 'data' => $fav, 'message' => 'Liked'];
    }

    public function removeFromFavorite($productId)
    {
        $user = Auth::user();

        if (!$user) {
            return ['status' => false, 'message' => 'User not authenticated'];
        }

        $fav = FavouriteOfProduct::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (!$fav) {
            return ['status' => false, 'message' => 'Product not liked'];
        }

        $fav->delete();

        return ['status' => true, 'message' => 'Unliked'];
    }
}