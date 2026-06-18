<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\File;

class GenerateTestTokens extends Command
{
    protected $signature = 'test:generate-tokens {count=150}';
    protected $description = 'Generate JWT tokens for users and save them to tokens.json for K6 stress testing';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $this->info("Starting JWT token generation for {$count} users...");

        $users = User::take($count)->get();

        if ($users->isEmpty()) {
            $this->error('No users found! Please run seeders first: php artisan db:seed');
            return 1;
        }

        $tokensData = [];

        foreach ($users as $user) {
            // JWT token
            $token = auth('api')->login($user);

            $tokensData[] = [
                'user_id' => $user->id,
                'email'   => $user->email,
                'token'   => $token,
            ];
        }

        $filePath = base_path('tokens.json');
        File::put($filePath, json_encode($tokensData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✅ Successfully generated JWT tokens for {$users->count()} users!");
        $this->info("📁 File saved at: {$filePath}");
        $this->info("📋 Copy tokens.json to D:\\nginx\\nginx-1.30.2\\");

        return 0;
    }
}