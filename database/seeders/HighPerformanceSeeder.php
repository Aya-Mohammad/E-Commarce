<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HighPerformanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Database\Seeders\High\UserHighSeeder::class,
            \Database\Seeders\High\StoreHighSeeder::class,
            \Database\Seeders\High\ProductHighSeeder::class,
            \Database\Seeders\High\CartHighSeeder::class,
        ]);
    }
}