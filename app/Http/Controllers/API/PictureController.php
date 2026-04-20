<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\UploadPicturesTrait;
use App\Models\Picture;

class PictureController extends Controller
{
    use UploadPicturesTrait;
    public function storePictureUsers(Request $request)
    {

        $path = $this->uploadPicture($request, 'users');
        Picture::create([
            'image_path' => $path
        ]);

        return 'ok';
    }
    public function storePictureAdmins(Request $request)
    {
        $path = $this->uploadPicture($request, 'admins');
        Picture::create([
            'image_path' => $path
        ]);

        return 'ok';
    }
}
