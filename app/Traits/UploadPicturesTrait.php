<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait UploadPicturesTrait
{
    public function uploadPicture($request, $fileKey, $folderName = 'uploads')
    {
        if (!$request->hasFile($fileKey)) {
            return null;
        }

        $file = $request->file($fileKey);

        $originalName = $file->getClientOriginalName();
        $fileName = Str::uuid() . '_' . $originalName;

        $path = $file->move(public_path($folderName), $fileName);

        return url("$folderName/$fileName");
    }
}