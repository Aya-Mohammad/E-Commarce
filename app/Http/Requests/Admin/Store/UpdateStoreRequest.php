<?php

namespace App\Http\Requests\Admin\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'image.*' => 'nullable|image|mimes:jpg,jpeg,png',
        ];
    }
}