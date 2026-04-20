<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
  // 'first_Name'=>$this->first_Name,
            // 'last_Name'=>$this->last_Name,
            // 'phone'=>$this->phone,
            // 'location'=>$this->location,