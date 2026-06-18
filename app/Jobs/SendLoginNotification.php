<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLoginNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $userAgent;

    public function __construct(User $user, $userAgent)
    {
        $this->user = $user;
        $this->userAgent = $userAgent;
    }

    public function handle()
    {
        sleep(2); 
        Log::info("Security Alert: Async Notification sent to user {$this->user->phone}. Device: {$this->userAgent}");
    }
}
