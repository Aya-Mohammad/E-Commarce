<?php

namespace App\Http\Requests\System\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductQuantityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
        ];
    }
}