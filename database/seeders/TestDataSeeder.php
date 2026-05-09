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
        // 1. User ثابت للاختبار
        $user = User::firstOrCreate(
            ['phone' => '0999999999'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'location' => 'Test City',
            ]
        );

        // 2. Products (بدون factory مشاكل)
        Product::query()->insert([
        [
            'name' => 'Product 1',
            'description' => 'Test product 1',
            'price' => 10,
            'quantity' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Product 2',
            'description' => 'Test product 2',
            'price' => 20,
            'quantity' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Product 3',
            'description' => 'Test product 3',
            'price' => 30,
            'quantity' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

        $products = Product::take(3)->get();

        // 3. Cart جاهز للـ Order
        foreach ($products as $product) {
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 2
            ]);
        }
    }
}