<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartDataSeeder extends Seeder
{
    public function run(): void
    {
        Cart::truncate();
        $user = User::where('phone', '0999999999')->first();
        $products = Product::take(3)->get();

        if (!$user || $products->isEmpty()) {
            $this->command->error('User or Products not found. Please run TestDataSeeder first!');
            return;
        }

        foreach ($products as $product) {
            Cart::create([
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'quantity'   => rand(1, 5), 
            ]);
        }

        $this->command->info('Cart seeded successfully for User: ' . $user->first_name);
    }
}