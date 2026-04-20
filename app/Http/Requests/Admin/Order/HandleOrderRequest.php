<?php

namespace App\Http\Requests\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;

class HandleOrderRequest extends FormRequest
{
    public function rules()
    {
        return [
            'action' => 'required|in:approve,reject',
        ];
    }
}