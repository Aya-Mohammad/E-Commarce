<?php

namespace App\Http\Requests\System\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $storeId = $this->route('id') ?? $this->route('store');

        return [
            'name'          => 'sometimes|required|string|max:255|unique:stores,name,' . $storeId,
            'description'   => 'sometimes|required|string|max:2000',
            'delivery_cost' => 'sometimes|required|numeric|min:0',
            'distance'      => 'sometimes|required|numeric|min:0',
            'start_of_work' => 'sometimes|required|date_format:H:i',
            'end_of_work'   => 'sometimes|required|date_format:H:i|after:start_of_work',
            'image_path'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}