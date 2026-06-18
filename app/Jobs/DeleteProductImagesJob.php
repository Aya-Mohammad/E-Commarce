<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteStoreImagesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->imagePaths as $path) {
            // نستخدم التخزين الخاص كما حددت
            if (Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
            }
        }
    }
}
