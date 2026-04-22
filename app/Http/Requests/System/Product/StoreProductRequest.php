<?php

namespace App\Http\Requests\System\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required',
            'quantity' => 'required|string',
            'store_id' => 'required',
            'image_path' => 'nullable|mimes:jpeg,png,jpg,gif',
        ];
    }
}
