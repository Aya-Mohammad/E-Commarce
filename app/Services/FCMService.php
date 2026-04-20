<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class FcmService
{
    protected string $serverKey;
    protected string $url;

    public function __construct()
    {
        $this->serverKey = env('FCM_SERVER_KEY');
        $this->url = env('FCM_URL', 'https://fcm.googleapis.com/fcm/send');
    }

    private function send(array $payload)
    {
        return Http::withHeaders([
            'Authorization' => 'key=' . $this->serverKey,
            'Content-Type' => 'application/json',
        ])->post($this->url, $payload)->json();
    }

    public function sendToUser(string $token, string $title, string $body, array $data = [])
    {
        return $this->send([
            'to' => $token,
            'notification' => compact('title', 'body'),
            'data' => $data,
        ]);
    }

    public function sendToAllUsers(string $title, string $body, array $data = [])
    {
        $tokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

        if (empty($tokens)) {
            return ['message' => 'No tokens found'];
        }

        return $this->send([
            'registration_ids' => $tokens,
            'notification' => compact('title', 'body'),
            'data' => $data,
        ]);
    }
}