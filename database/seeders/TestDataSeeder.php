<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['phone' => '0999999999'],
            [
                'first_name' => 'Test',
                'last_name'  => 'User',
                'password'   => Hash::make('password'),
                'location'   => 'Test City',
            ]
        );

        Cart::where('user_id', $user->id)->delete();
        
        \App\Models\OrderItem::whereIn(
            'order_id',
            \App\Models\Order::where('user_id', $user->id)->pluck('id')
        )->delete();
        
        \App\Models\Order::where('user_id', $user->id)->delete();

        Product::query()->update(['quantity' => 1000]);

        if (Product::count() === 0) {
            Product::insert([
                ['name' => 'Product 1', 'description' => 'Test 1', 'price' => 10, 'quantity' => 1000, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Product 2', 'description' => 'Test 2', 'price' => 20, 'quantity' => 1000, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Product 3', 'description' => 'Test 3', 'price' => 30, 'quantity' => 1000, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $products = Product::take(3)->get();

        foreach ($products as $product) {
            Cart::create([
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'quantity'   => 2,
            ]);
        }
    }
}