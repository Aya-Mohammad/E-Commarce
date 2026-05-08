<?php

namespace App\Http\Requests\System\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $productId = $this->route('id') ?? $this->route('product');

        return [
            'name'        => 'sometimes|required|string|max:255|unique:products,name,' . $productId,
            'description' => 'sometimes|required|string|max:2000',
            'price'       => 'sometimes|required|numeric|min:0',
            'quantity'    => 'sometimes|required|integer|min:0',
            'store_id'    => 'sometimes|required|integer|exists:stores,id',
            'image_path'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}