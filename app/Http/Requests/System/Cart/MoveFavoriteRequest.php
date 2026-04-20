<?php

namespace App\Http\Requests\System\Cart;

use Illuminate\Foundation\Http\FormRequest;

class MoveFavoriteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
        ];
    }
}