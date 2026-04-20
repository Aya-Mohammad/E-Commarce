<?php

namespace App\Services\System;

use App\Models\Cart;
use App\Models\Product;
use App\Models\FavouriteOfProduct;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function add(array $data)
    {
        $product = Product::find($data['product_id']);

        if ($product->quantity < $data['quantity']) {
            return ['error' => 'الكمية غير متوفرة'];
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
        $cart = Cart::find($id);

        if (!$cart) {
            return ['error' => 'not found'];
        }

        $cart->delete();

        return true;
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
                ->first();

            if (!$favorite) {
                return ['error' => 'not found'];
            }

            $product = Product::find($favorite->product_id);

            if (!$product) {
                return ['error' => 'product not found'];
            }

            if ($product->quantity < $data['quantity']) {
                return ['error' => 'not enough stock'];
            }

            $cartItem = Cart::where('user_id', $favorite->user_id)
                ->where('product_id', $favorite->product_id)
                ->first();

            if ($cartItem) {
                $cartItem->update([
                    'quantity' => $cartItem->quantity + $data['quantity']
                ]);
            } else {
                Cart::create([
                    'user_id' => $favorite->user_id,
                    'product_id' => $favorite->product_id,
                    'quantity' => $data['quantity'],
                ]);
            }

            $product->quantity -= $data['quantity'];
            $product->save();

            return true;
        });
    }
}