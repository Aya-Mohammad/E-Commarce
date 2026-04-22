<?php

namespace App\Http\Requests\System\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|unique:stores,name,' . $this->route('id'),
            'description' => 'sometimes|required|string',
            'delivery_cost' => 'sometimes|required|numeric',
            'distance' => 'sometimes|required|string',
            'start_of_work' => 'sometimes|required|string',
            'end_of_work' => 'sometimes|required|string',
        ];
    }
}
