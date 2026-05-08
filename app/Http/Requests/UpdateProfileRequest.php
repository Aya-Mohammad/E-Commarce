<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|between:3,25',
            'last_name'  => 'required|string|between:3,25',
            'location'   => 'required|string|between:2,50',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}