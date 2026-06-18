<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'quantity'    => ['required', 'integer', 'min:0'],
            'store_id'    => ['required', 'integer', 'exists:stores,id'], 
            'images'      => ['nullable', 'array', 'max:5'], 
            'images.*'    => ['image', 'mimes:jpeg,png', 'max:2048'], 
        ];
    }
}