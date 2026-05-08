<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

trait UploadPicturesTrait
{
    public function uploadPicture($request, $fileKey, $folderName = 'uploads')
    {
        if (!$request->hasFile($fileKey)) {
            return null;
        }

        $file = $request->file($fileKey);

        $realMimeType = $file->getMimeType();
        if (!in_array($realMimeType, ['image/jpeg', 'image/png'])) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName  = Str::uuid() . '.' . $extension;

        $path = $file->storeAs($folderName, $fileName, 'private');

        return Storage::disk('private')->url($path);
    }
}