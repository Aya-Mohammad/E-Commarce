<?php

namespace App\Http\Requests\System\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|unique:stores,name,' . $this->route('id'),
            'location' => 'sometimes|required|string',
            'image_path' => 'nullable|mimes:jpeg,png,jpg,gif',
        ];
    }
}
