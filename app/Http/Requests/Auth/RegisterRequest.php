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
            'first_name' => ['required', 'string', 'between:3,25'],
            'last_name'  => ['required', 'string', 'between:3,25'],
            'phone'      => ['required', 'regex:/^[0-9]{9}$/', 'unique:users,phone'],
            'password'   => ['required', 'string', 'min:8'],
            'location'   => ['required', 'string', 'between:2,50'],

            'image_path' => [
                'nullable',
                'image',
                'max:2048',
                'mimetypes:image/jpeg,image/png,image/jpg'
            ],

            'fcm_token' => ['nullable', 'string'],
        ];
    }
}
