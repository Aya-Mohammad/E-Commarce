<?php

namespace App\Http\Requests\System\Favorite;

use Illuminate\Foundation\Http\FormRequest;

class CheckFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
        ];
    }
}