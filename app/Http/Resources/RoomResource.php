<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'category_id' => $this->category_id,
            'name' => $this->name,
            'size' => $this->size,
            'bed' => $this->bed,
            'bathroom_facilities' => $this->bathroom_facilities,
            'amenities' => $this->amenities,
            'directions_view' => $this->directions_view,
            'description' => $this->description,
            'price' => $this->price,
            'max_people' => $this->max_people,
            'status' => $this->status,
            'is_smoking' => $this->is_smoking,
            'created_at' => $this->created_at->format('Y-m-d'),
            'updated_at' => $this->updated_at->format('Y-m-d'),
            'category' => $this->category,
            'images' => $this->roomImage,
        ];
    }
}
