<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|between:3,25',
            'username' => 'required|string|between:3,25|unique:admins,username',
            'phone' => 'required|digits:9|unique:admins,phone',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'image_path' => 'nullable|mimes:jpeg,png,jpg',
        ];
    }
}