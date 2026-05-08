<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:2000',
            'price'       => 'sometimes|required|numeric|min:0',
            'quantity'    => 'sometimes|required|integer|min:0',
            'store_id'    => 'sometimes|required|integer|exists:stores,id',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}