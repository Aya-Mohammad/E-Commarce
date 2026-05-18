<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Store;
use App\Models\Cart;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class NFRSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Cart::truncate();
        User::where('phone', 'like', '0900%')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $store = Store::firstOrCreate(['name' => 'UniVibe Main Store'], [
            'delivery_cost' => 20,
            'distance' => '5 km',
            'start_of_work' => '09:00',
            'end_of_work' => '21:00',
        ]);

        $product = Product::updateOrCreate(
            ['id' => 1], 
            [
                'name' => 'Scent Diffuser',
                'price' => 50,
                'quantity' => 10, 
                'store_id' => $store->id,
            ]
        );

        $this->command->info('Creating 100 users and their carts...');
        
        for ($i = 1; $i <= 100; $i++) {
            $user = User::create([
                'first_name' => "User",
                'last_name'  => "Test_$i",
                'phone'      => "0900" . str_pad($i, 6, "0", STR_PAD_LEFT), 
                'password'   => Hash::make('password'),
                'location'   => 'Test Location',
            ]);

            Cart::create([
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'quantity'   => 1, 
            ]);
        }

        $this->command->info('NFR Data prepared successfully.');
    }
}