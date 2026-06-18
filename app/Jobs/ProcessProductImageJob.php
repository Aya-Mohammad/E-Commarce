<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class ProcessProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $imagePath;

    public function __construct($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public function handle(): void
    {
        // 1. جلب محتوى الصورة من القرص الخاص (Private)
        $content = Storage::disk('private')->get($this->imagePath);

        if (! $content) {
            return;
        }

        // 2. معالجة الصورة باستخدام Intervention Image
        $img = Image::make($content)
            // تصغير العرض لـ 800 بكسل مع الحفاظ على التناسب (Proportions)
            ->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize(); // منع تكبير الصور الصغيرة جداً
            })
            // تحويلها لصيغة WebP أو تقليل جودة الـ JPEG لـ 75% لتوفير المساحة
            ->encode('jpg', 75);

        // 3. إعادة حفظ الصورة المعالجة فوق النسخة الأصلية
        Storage::disk('private')->put($this->imagePath, $img->getEncoded());

        // اختيارياً: يمكنك مسح الذاكرة يدوياً لضمان عدم حدوث Memory Leak مع Octane
        $img->destroy();
    }
}
