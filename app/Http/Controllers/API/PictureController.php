<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\UploadPicturesTrait;
use App\Traits\ApiResponseTrait;
use App\Models\Picture;

class PictureController extends Controller
{
    use UploadPicturesTrait, ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function storePictureUsers(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $path = $this->uploadPicture($request, 'image', 'uploads/users');

        if (!$path) {
            return $this->apiResponse(null, 'Failed to upload image', 500);
        }

        $picture = Picture::create(['image_path' => $path]);

        return $this->apiResponse($picture, 'Image uploaded successfully', 201);
    }

    public function storePictureAdmins(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $path = $this->uploadPicture($request, 'image', 'uploads/admins');

        if (!$path) {
            return $this->apiResponse(null, 'Failed to upload image', 500);
        }

        $picture = Picture::create(['image_path' => $path]);

        return $this->apiResponse($picture, 'Image uploaded successfully', 201);
    }
}