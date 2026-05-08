<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'     => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'password'  => ['required', 'string', 'min:6'],
            'fcm_token' => ['nullable', 'string'],
        ];
    }
}