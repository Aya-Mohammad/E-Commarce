<?php

namespace App\Http\Requests\System\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'required|integer|min:0',
        ];
    }
}