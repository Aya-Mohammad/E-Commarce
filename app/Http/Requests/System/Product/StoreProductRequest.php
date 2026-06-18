<?php

namespace App\Http\Requests\System\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:products,name',
            'description' => 'required|string|max:2000',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:0',
            'store_id'    => 'required|integer|exists:stores,id',
            'image_path'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}