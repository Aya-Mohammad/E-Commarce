<?php

namespace App\Http\Requests\System\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|unique:products,name,' . $this->route('id'),
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric',
            'quantity' => 'sometimes|required|integer',
            'store_id' => 'sometimes|required|exists:stores,id',
            'image_path' => 'nullable|mimes:jpeg,png,jpg,gif',
        ];
    }
}
