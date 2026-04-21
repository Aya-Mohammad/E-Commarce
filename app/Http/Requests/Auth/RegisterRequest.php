<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|between:3,25',
            'last_name' => 'required|string|between:3,25',
            'phone' => 'required|digits:9|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'location' => 'required|string|between:2,50',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'fcm_token' => 'nullable|string',
        ];
    }
}
