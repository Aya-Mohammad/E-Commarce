<?php

namespace App\Http\Requests\Admin\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'image.*' => 'nullable|image|mimes:jpg,jpeg,png',
        ];
    }
}