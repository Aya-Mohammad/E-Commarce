<?php

namespace App\Http\Requests\System\Favorite;

use Illuminate\Foundation\Http\FormRequest;

class AddFavoriteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
        ];
    }

    public function messages()
    {
        return [
            'product_id.required' => 'Product ID is required',
            'product_id.exists' => 'Product not found',
        ];
    }
}