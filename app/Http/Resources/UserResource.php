<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'location' => $this->location,
            'password' => $this->password, // يتم عرض كلمة المرور هنا
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
