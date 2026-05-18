<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Store;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        OrderItem::truncate();
        Order::truncate();
        Product::truncate();
        Store::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $store = Store::create([
            'name' => 'UniVibe Main Store',
            'description' => 'The main store for UniVibe products.',
            'delivery_cost' => 20,
            'distance' => '5 km',
            'start_of_work' => '09:00',
            'end_of_work' => '21:00',
            ]);

        $user = User::firstOrCreate(
            ['phone' => '0999999999'],
            [
                'first_name' => 'Test',
                'last_name'  => 'User',
                'password'   => Hash::make('password'),
                'location'   => 'Test Location',
            ]
        );

        $p1 = Product::create([
            'name' => 'Scent Diffuser',
            'price' => 50,
            'quantity' => 100,
            'store_id' => $store->id,
            'description' => 'A device that disperses essential oils into the air, creating a pleasant aroma and atmosphere.',
        ]);

        $p2 = Product::create([
            'name' => 'Air Purifier',
            'price' => 150,
            'quantity' => 50,
            'store_id' => $store->id,
            'description' => 'A device that removes contaminants from the air, improving indoor air quality.',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'approved',
                'total_price' => 200,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $p1->id,
                'quantity' => 1,
                'price' => 50,
            ]);

        }

        $this->command->info('Success! User ID is: ' . $user->id);
    }
}