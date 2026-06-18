<?php

namespace Database\Seeders\High;

use Illuminate\Database\Seeder;
use App\Models\Store;

class StoreHighSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [];

        for ($i = 1; $i <= 50; $i++) {
            $stores[] = [
                'id'            => $i,
                'name'          => "Store {$i}",
                'description'   => "This is the description for Store {$i}",
                'delivery_cost' => rand(5, 25), // تكلفة توصيل عشوائية
                'distance'      => rand(1, 10) . " km", // مسافة افتراضية
                'start_of_work' => '08:00',
                'end_of_work'   => '22:00',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        Store::insert($stores);
    }
}