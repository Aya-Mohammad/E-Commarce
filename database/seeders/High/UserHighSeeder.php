<?php

namespace Database\Seeders\High;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserHighSeeder extends Seeder
{
    public function run(): void
    {
        $users = [];

        for ($i = 1; $i <= 150; $i++) {

            $users[] = [
                'first_name' => "User",
                'last_name'  => "{$i}",
                'phone'      => 963900000000 + $i, // توليد رقم موبايل فرعي لكل مستخدم
                'password'   => Hash::make('password'), // تشفير محمي ومتوافق
                'location'   => "Location {$i}",
                'role'       => 'user', // القيمة الافتراضية المحددة بالميجريشن
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        User::insert($users);
    }
}