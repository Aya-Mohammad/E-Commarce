<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'quantity' => 'sometimes|integer',
            'store_id' => 'sometimes|exists:stores,id',
            'image.*' => 'nullable|image|mimes:jpg,jpeg,png',
        ];
    }
}