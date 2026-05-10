<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
// لا تنسي استدعاء Str إذا كنتِ تستخدمينها
use Illuminate\Support\Str; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUserImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $filePath
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        
        $user->image()->create([
            'image_path' => $this->filePath,
        ]);
    }
}