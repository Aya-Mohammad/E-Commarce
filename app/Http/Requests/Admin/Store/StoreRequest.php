<?php

namespace App\Http\Requests\Admin\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'delivery_cost' => ['required', 'numeric', 'min:0'],
            'distance'      => ['required', 'numeric', 'min:0'],
            'start_of_work' => ['required', 'date_format:H:i'], 
            'end_of_work'   => ['required', 'date_format:H:i', 'after:start_of_work'],           
            'images'        => ['nullable', 'array', 'max:5'],
            'images.*'      => ['image', 'mimes:jpeg,png', 'max:2048'],
        ];
    }
}