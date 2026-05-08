<?php

namespace App\Http\Requests\Admin\Auth;

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
            'name'       => 'required|string|between:3,25',
            'username'   => 'required|string|between:3,25|unique:admins,username',
            'phone'      => 'required|digits:9|unique:admins,phone',
            'email'      => 'required|email|max:255|unique:admins,email',
            'password'   => 'required|string|min:8|confirmed',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}