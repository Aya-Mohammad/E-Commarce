<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_order()
    {
        // seed data
        $this->seed(\Database\Seeders\TestDataSeeder::class);

        $user = User::where('phone', '0999999999')->first();

        // login (حسب JWT عندك)
        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/orders/place');

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
        ]);
    }
}