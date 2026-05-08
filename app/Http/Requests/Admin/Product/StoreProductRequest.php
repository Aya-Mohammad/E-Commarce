<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:0',
            'store_id'    => 'required|integer|exists:stores,id',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}