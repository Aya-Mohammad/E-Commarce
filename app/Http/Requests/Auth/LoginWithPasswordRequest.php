<?php

namespace App\Http\Ayth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginWithPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required',
            'password' => 'required',
            'fcm_token' => 'required'
        ];
    }
}

