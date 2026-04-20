<?php

namespace App\Http\Requests\System\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:stores,name',
            'discraption' => 'required|string',
            'delivery_cost' => 'required',
            'distance' => 'required',
            'start_of_work' => 'required|string',
            'end_of_work' => 'required|string',
            'image_path' => 'nullable|mimes:jpeg,png,jpg,gif',
        ];
    }
}