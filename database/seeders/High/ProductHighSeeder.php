<?php

namespace Database\Seeders\High;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductHighSeeder extends Seeder
{
    public function run(): void
    {
        $products = [];

        $products[] = [
            'id'          => 1,
            'name'        => 'Hot Product',
            'description' => 'Overselling test product',
            'price'       => 100.0,
            'quantity'    => 10,
            'store_id'    => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];

        for ($i = 2; $i <= 200; $i++) {
            $products[] = [
                'id'          => $i,
                'name'        => "Product {$i}",
                'description' => 'Test product description',
                'price'       => rand(50, 500),
                'quantity'    => 100,
                'store_id'    => rand(1, 3), 
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        Product::insert($products);
    }
}