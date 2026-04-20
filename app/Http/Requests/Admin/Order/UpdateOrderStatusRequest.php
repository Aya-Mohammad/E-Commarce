<?php

namespace App\Http\Requests\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function rules()
    {
        return [
            'status' => 'required|string|in:pending,approved,rejected,delivering,delivered',
        ];
    }
}