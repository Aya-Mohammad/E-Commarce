<?php

namespace App\Http\Requests\System\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255|unique:stores,name',
            'description'   => 'required|string|max:2000',
            'delivery_cost' => 'required|numeric|min:0',
            'distance'      => 'required|numeric|min:0',
            'start_of_work' => 'required|date_format:H:i',
            'end_of_work'   => 'required|date_format:H:i|after:start_of_work',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}