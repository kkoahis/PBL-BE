<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'name' => $this->name,
            'description' => $this->description,

            'size' => $this->size,
            'bed' => $this->bed,
            'bathroom_facilities' => $this->bathroom_facilities,
            'amenities' => $this->amenities,
            'directions_view' => $this->directions_view,
            'description' => $this->description,
            'price' => $this->price,
            'max_people' => $this->max_people,
            'is_smoking' => $this->is_smoking,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'rooms' => $this->room,

            'category_images' => $this->categoryImage,
        ];
    }
}
