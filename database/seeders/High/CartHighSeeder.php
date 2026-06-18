<?php

namespace Database\Seeders\High;

use Illuminate\Database\Seeder;
use App\Models\Cart;

class CartHighSeeder extends Seeder
{
    public function run(): void
    {
        $cartItems = [];

        for ($userId = 1; $userId <= 150; $userId++) {
            $productId = rand(1, 10) <= 7
                ? 1
                : rand(2, 200); 

            $cartItems[] = [
                'user_id'    => $userId,
                'product_id' => $productId,
                'quantity'   => rand(1, 3),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Cart::insert($cartItems);
    }
}