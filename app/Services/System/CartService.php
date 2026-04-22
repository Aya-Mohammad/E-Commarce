<?php

namespace App\Services\System;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function add(array $data)
    {
        $product = Product::find($data['product_id']);

        if (!$product) {
            return ['error' => 'product not found', 'status' => 404];
        }

        if ($product->quantity < $data['quantity']) {
            return ['error' => 'الكمية غير متوفرة', 'status' => 422];
        }

        return Cart::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'product_id' => $data['product_id'],
            ],
            [
                'quantity' => $data['quantity'],
            ]
        );
    }

    public function remove($id)
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$cart) {
            return ['error' => 'not found', 'status' => 404];
        }

        $cart->delete();

        return true;
    }

    public function updateQuantity($id, $quantity)
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$cart) {
            return ['error' => 'not found', 'status' => 404];
        }

        $product = Product::find($cart->product_id);

        if (!$product) {
            return ['error' => 'product not found', 'status' => 404];
        }

        if ($product->quantity < $quantity) {
            return ['error' => 'الكمية غير متوفرة', 'status' => 422];
        }

        $cart->update([
            'quantity' => $quantity,
        ]);

        return $cart->fresh('product');
    }

    public function show()
    {
        $cartItems = Cart::where('user_id', auth()->id())
            ->with('product')
            ->get();

        return $cartItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ];
        });
    }

    public function moveFavorite(array $data, $favoriteId)
    {
        return DB::transaction(function () use ($data, $favoriteId) {
            $favorite = DB::table('favourite_of_products')
                ->where('id', $favoriteId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$favorite) {
                return ['error' => 'not found', 'status' => 404];
            }

            $product = Product::find($favorite->product_id);

            if (!$product) {
                return ['error' => 'product not found', 'status' => 404];
            }

            $cartItem = Cart::where('user_id', $favorite->user_id)
                ->where('product_id', $favorite->product_id)
                ->first();

            $targetQuantity = ($cartItem?->quantity ?? 0) + $data['quantity'];

            if ($product->quantity < $targetQuantity) {
                return ['error' => 'not enough stock', 'status' => 422];
            }

            if ($cartItem) {
                $cartItem->update([
                    'quantity' => $targetQuantity
                ]);
            } else {
                $cartItem = Cart::create([
                    'user_id' => $favorite->user_id,
                    'product_id' => $favorite->product_id,
                    'quantity' => $data['quantity'],
                ]);
            }

            return $cartItem;
        });
    }
}
