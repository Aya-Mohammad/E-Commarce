<?php

namespace App\Services\System;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartService
{
    # we have (Race Condition) - we use (Transaction + Pessimistic Locking)
    # Better use (Optimistic Locking)
    # Add (Cache Invalidation for product quantity)
    # Add (Capacity Control - max quantity per product per user)
    public function add(array $data)
    {
        return DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->find($data['product_id']);

            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }

            $existingCart = Cart::where('user_id', auth()->id())
                ->where('product_id', $data['product_id'])
                ->first();

            $targetQuantity = ($existingCart?->quantity ?? 0) + $data['quantity'];

            if ($product->quantity < $targetQuantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }

            return Cart::updateOrCreate(
                [
                    'user_id'    => auth()->id(),
                    'product_id' => $data['product_id'],
                ],
                [
                    'quantity' => $targetQuantity,
                ]
            );
        });
    }

    # No Transaction - safe here (single delete, no stock changes)
    # Add (Cache Invalidation for cart) 
    public function remove($id)
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$cart) {
            return ['error' => 'Cart item not found', 'status' => 404];
        }

        $cart->delete();

        return true;
    }

    # we have (Race Condition) - we use (Transaction + Pessimistic Locking)
    # Better use (Optimistic Locking)
    # Add (Validation - quantity must be > 0)
    # Add (Cache Invalidation)
    public function updateQuantity($id, $quantity)
    {
        return DB::transaction(function () use ($id, $quantity) {
            $cart = Cart::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$cart) {
                return ['error' => 'Cart item not found', 'status' => 404];
            }

            $product = Product::lockForUpdate()->find($cart->product_id);

            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }

            if ($product->quantity < $quantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }

            $cart->update(['quantity' => $quantity]);

            return $cart->fresh('product');
        });
    }

    # Add (Pagination - large carts will load all items at once)
    # Add (Caching (Redis) - cart data changes rarely, good candidate for Cache)
    public function show()
    {
        $cartItems = Cart::where('user_id', auth()->id())
            ->with('product')
            ->get();

        return $cartItems->map(function ($item) {
            if (!$item->product) {
                return [
                    'id'           => $item->id,
                    'product_id'   => $item->product_id,
                    'product_name' => 'Product unavailable',
                    'quantity'     => $item->quantity,
                    'price'        => null,
                ];
            }

            return [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product->name,
                'quantity'     => $item->quantity,
                'price'        => $item->product->price,
            ];
        });
    }

    # we have (Race Condition) - we use (Transaction + Pessimistic Locking)
    # Better use (Optimistic Locking)
    # Add (Cache Invalidation - for both cart and favourites)
    # Risk: uses raw DB::table() for favourite - inconsistent with Eloquent pattern
    public function moveFavorite(array $data, $favoriteId)
    {
        return DB::transaction(function () use ($data, $favoriteId) {
            $favorite = DB::table('favourite_of_products')
                ->where('id', $favoriteId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$favorite) {
                return ['error' => 'Favorite item not found', 'status' => 404];
            }

            $product = Product::lockForUpdate()->find($favorite->product_id);

            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }

            $cartItem = Cart::where('user_id', $favorite->user_id)
                ->where('product_id', $favorite->product_id)
                ->first();

            $targetQuantity = ($cartItem?->quantity ?? 0) + $data['quantity'];

            if ($product->quantity < $targetQuantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }

            if ($cartItem) {
                $cartItem->update(['quantity' => $targetQuantity]);
            } else {
                $cartItem = Cart::create([
                    'user_id'    => $favorite->user_id,
                    'product_id' => $favorite->product_id,
                    'quantity'   => $data['quantity'],
                ]);
            }

            DB::table('favourite_of_products')->where('id', $favoriteId)->delete();

            return $cartItem;
        });
    }
}